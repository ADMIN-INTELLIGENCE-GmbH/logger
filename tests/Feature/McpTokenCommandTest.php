<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class McpTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        Project::create(['name' => 'Shop'])->users()->attach($user->id, ['permission' => 'view']);

        return $user;
    }

    /** @return array{0: int, 1: string} exit code and output */
    private function make(User $user, string $scopes, int $days = 30): array
    {
        $code = Artisan::call('mcp:token', ['--user' => $user->email, '--name' => 'Claude', '--scopes' => $scopes, '--days' => $days, '--no-interaction' => true]);

        return [$code, Artisan::output()];
    }

    public function test_scopes_are_capped_at_the_users_rights(): void
    {
        $user = $this->viewer();

        [$code] = $this->make($user, 'read,get_ingest_config,create_project');

        $this->assertSame(0, $code);
        $this->assertSame(['mcp', 'mcp:read'], $user->tokens()->first()->abilities);
    }

    public function test_admin_can_get_create_project_and_no_expiry(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->make($admin, 'create_project', 0);

        $token = $admin->tokens()->first();
        $this->assertSame(['mcp', 'mcp:create_project'], $token->abilities);
        $this->assertNull($token->expires_at);
    }

    public function test_refuses_without_an_allowed_scope(): void
    {
        $user = $this->viewer();

        [$code] = $this->make($user, 'create_project');

        $this->assertSame(1, $code);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_printed_token_works_on_mcp_and_lists_only_its_tools(): void
    {
        $user = $this->viewer();

        [, $output] = $this->make($user, 'read');

        $this->assertStringContainsString('claude mcp add --transport http --scope user logger '.url('/mcp'), $output);
        $this->assertStringContainsString('shown only now', $output);
        $this->assertSame(1, preg_match('/Bearer (\d+\|\w+)"/', $output, $m));

        $tools = $this->withToken($m[1])->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => (object) []])
            ->assertOk()->json('result.tools');
        $this->assertEqualsCanonicalizing(['list_projects', 'project_overview', 'new_log_groups', 'search_logs', 'get_log'], array_column($tools, 'name'));
    }

    public function test_interactive_flow_picks_user_and_scopes(): void
    {
        $user = $this->viewer();
        $user->update(['name' => 'Jane Roe']);

        $this->artisan('mcp:token')
            ->expectsSearch('User', $user->id, 'jane', [$user->id => "Jane Roe <{$user->email}>"])
            ->expectsQuestion('Token name', 'Claude')
            ->expectsChoice('Expires', '365', ['30' => '30 days', '90' => '90 days', '365' => '1 year', '0' => 'Never'])
            ->expectsChoice('What this token may do', ['read'], ['read' => 'Read logs: list_projects, project_overview, new_log_groups, search_logs, get_log'])
            ->expectsOutputToContain('shown only now')
            ->assertSuccessful();

        $token = $user->tokens()->first();
        $this->assertSame(['mcp', 'mcp:read'], $token->abilities);
        $this->assertSame(now()->addDays(365)->toDateString(), $token->expires_at->toDateString());
    }

    public function test_wipe_removes_only_mcp_tokens(): void
    {
        $user = $this->viewer();
        $user->createToken('legacy', ['mcp']);
        $user->createToken('scoped', ['mcp', 'mcp:read']);
        $user->createToken('other', ['other']);
        $stranger = User::factory()->create();
        $stranger->createToken('theirs', ['mcp']);

        $this->artisan('mcp:token:wipe', ['user' => $user->email])
            ->expectsConfirmation("Delete these 2 tokens of {$user->email}?", 'yes')
            ->expectsOutputToContain('Deleted 2 MCP token(s).')
            ->assertSuccessful();

        $this->assertSame(['other'], $user->tokens()->pluck('name')->all());
        $this->assertSame(1, $stranger->tokens()->count());
    }

    public function test_wipe_keeps_tokens_when_not_confirmed(): void
    {
        $user = $this->viewer();
        $user->createToken('legacy', ['mcp']);

        $this->artisan('mcp:token:wipe', ['user' => (string) $user->id])
            ->expectsConfirmation("Delete these 1 tokens of {$user->email}?", 'no')
            ->assertSuccessful();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_profile_token_routes_are_gone_and_profile_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/tokens', ['token_name' => 'x', 'token_days' => 30, 'token_scopes' => ['read']])
            ->assertNotFound();
        $this->assertFalse(app('router')->has('profile.tokens.store'));
        $this->assertFalse(app('router')->has('profile.tokens.destroy'));
        $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('API Tokens');
        $this->assertSame(0, $user->tokens()->count());
    }
}
