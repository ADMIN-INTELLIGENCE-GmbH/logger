<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshToken;
use Tests\TestCase;

/**
 * OAuth for KIRA: KIRA is a confidential client (MCP_KIRA_CLIENT_ID) whose
 * tokens see only read-only tools. Anything else on /mcp needs mcp.external.
 */
class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    private const REDIRECT = 'http://kira.test/settings/sources/logger/callback';

    private const READ_TOOLS = ['list_projects', 'project_overview', 'new_log_groups', 'search_logs', 'get_log'];

    private Client $kira;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kira = app(ClientRepository::class)->createAuthorizationCodeGrantClient('KIRA', [self::REDIRECT]);
        config(['services.kira.client_id' => $this->kira->getKey()]);
    }

    private function rpc(string $method, array $params = [], ?string $token = null): TestResponse
    {
        $headers = $token === null ? [] : ['Authorization' => 'Bearer '.$token];

        return $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params], $headers);
    }

    /** @return list<string> */
    private function toolNames(?string $token = null): array
    {
        return array_column($this->rpc('tools/list', [], $token)->assertOk()->json('result.tools'), 'name');
    }

    private function otherClient(): Client
    {
        return app(ClientRepository::class)->createAuthorizationCodeGrantClient('Claude', ['https://claude.ai/api/mcp/auth_callback'], false);
    }

    public function test_discovery_endpoints_answer_json(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')->assertOk()
            ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
            ->assertJsonPath('token_endpoint', url('/oauth/token'))
            ->assertJsonPath('code_challenge_methods_supported', ['S256']);
        $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk()
            ->assertJsonPath('resource', url('/mcp'));
    }

    public function test_kira_flow_gives_a_read_only_token_with_rotating_refresh(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Shop']);
        $project->users()->attach($user->id, ['permission' => 'edit']);

        $verifier = Str::random(64);
        $consent = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => $this->kira->getKey(),
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'mcp:read',
            'state' => 'xyz',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]))->assertOk()->assertSee('KIRA');

        $approved = $this->post('/oauth/authorize', [
            'state' => 'xyz',
            'client_id' => $this->kira->getKey(),
            'auth_token' => $consent->viewData('authToken'),
        ])->assertRedirect();
        parse_str((string) parse_url((string) $approved->headers->get('Location'), PHP_URL_QUERY), $query);

        $tokens = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->kira->getKey(),
            'client_secret' => $this->kira->plainSecret,
            'redirect_uri' => self::REDIRECT,
            'code_verifier' => $verifier,
            'code' => $query['code'],
        ])->assertOk()->assertJsonPath('expires_in', 3600)->json();

        $refresh = RefreshToken::query()->sole();
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $refresh->expires_at->timestamp, 60);

        // From here on only the bearer token speaks, not the browser session.
        Auth::guard('web')->logout();
        Auth::forgetGuards();

        $this->assertEqualsCanonicalizing(self::READ_TOOLS, $this->toolNames($tokens['access_token']));
        $this->rpc('tools/call', ['name' => 'list_projects', 'arguments' => (object) []], $tokens['access_token'])
            ->assertOk()->assertSee('Shop');

        $write = $this->rpc('tools/call', ['name' => 'resolve_log_group', 'arguments' => ['project' => 'Shop', 'log_id' => 1]], $tokens['access_token']);
        $this->assertNotNull($write->json('error'));
        $this->assertNull($write->json('result'));

        $renewed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->kira->getKey(),
            'client_secret' => $this->kira->plainSecret,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();
        $this->assertNotSame($tokens['refresh_token'], $renewed['refresh_token']);

        // Rotation: the used refresh token is spent.
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->kira->getKey(),
            'client_secret' => $this->kira->plainSecret,
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(400);
    }

    public function test_kira_token_cannot_call_write_tools_even_with_mcp_external(): void
    {
        Passport::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'mcp_external' => true]), ['mcp:read', 'mcp:use'], 'api', $this->kira);

        $this->assertEqualsCanonicalizing(self::READ_TOOLS, $this->toolNames());
        $call = $this->rpc('tools/call', ['name' => 'create_project', 'arguments' => ['name' => 'Evil']]);
        $this->assertNotNull($call->json('error'));
        $this->assertDatabaseMissing('projects', ['name' => 'Evil']);
    }

    public function test_other_oauth_client_needs_mcp_external(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use'], 'api', $this->otherClient());

        $this->rpc('tools/list')->assertForbidden();

        $user->forceFill(['mcp_external' => true])->save();
        $this->assertCount(8, $this->toolNames());
    }

    public function test_sanctum_token_needs_mcp_external(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('x', ['mcp'])->plainTextToken;

        $this->rpc('tools/list', [], $token)->assertForbidden();

        $user->forceFill(['mcp_external' => true])->save();
        Auth::forgetGuards();
        $this->assertCount(8, $this->toolNames($token));
    }

    public function test_authorize_refuses_other_clients_without_mcp_external(): void
    {
        $user = User::factory()->create();
        $client = $this->otherClient();
        $url = '/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'response_type' => 'code',
            'state' => 'xyz',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', Str::random(64), true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);

        $this->actingAs($user)->get($url)->assertForbidden()->assertSee('Only KIRA may');

        $user->forceFill(['mcp_external' => true])->save();
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Claude');
    }
}
