<?php

namespace Tests\Feature;

use App\Mcp\Servers\LoggerServer;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\GetIngestConfigTool;
use App\Mcp\Tools\GetLogTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\NewLogGroupsTool;
use App\Mcp\Tools\ProjectOverviewTool;
use App\Mcp\Tools\ResolveLogGroupTool;
use App\Mcp\Tools\SearchLogsTool;
use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\TransientToken;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $permission): array
    {
        $user = User::factory()->create(['role' => User::ROLE_USER, 'mcp_external' => true]);
        $project = Project::create(['name' => 'Shop']);
        $project->users()->attach($user->id, ['permission' => $permission]);

        return [$user, $project];
    }

    /** Tool calls run as a token user; a TransientToken carries every ability. */
    private function withMcpToken(User $user): User
    {
        return $user->withAccessToken(new TransientToken);
    }

    private function rpc(string $token, string $method, array $params = []): TestResponse
    {
        return $this->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params]);
    }

    private function log(Project $project, string $level, string $message, $at = null): Log
    {
        return Log::create([
            'project_id' => $project->id,
            'level' => $level,
            'message' => $message,
            'logged_at' => $at ?? now(),
        ]);
    }

    public function test_endpoint_requires_token_with_mcp_ability(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])
            ->assertUnauthorized();

        $user = User::factory()->create();
        $wrong = $user->createToken('x', ['other'])->plainTextToken;
        $this->withToken($wrong)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])
            ->assertForbidden();
    }

    public function test_token_with_mcp_ability_reaches_server(): void
    {
        $user = User::factory()->create(['mcp_external' => true]);
        $token = $user->createToken('x', ['mcp'])->plainTextToken;

        $this->withToken($token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-03-26', 'capabilities' => (object) [], 'clientInfo' => ['name' => 't', 'version' => '1']],
        ])->assertOk()->assertJsonPath('result.serverInfo.name', 'Logger');
    }

    public function test_scoped_token_lists_and_calls_only_its_tools(): void
    {
        [$user] = $this->member('edit');
        $token = $user->createToken('x', ['mcp', 'mcp:read'])->plainTextToken;

        $names = array_column($this->rpc($token, 'tools/list')->assertOk()->json('result.tools'), 'name');
        $this->assertEqualsCanonicalizing(['list_projects', 'project_overview', 'new_log_groups', 'search_logs', 'get_log'], $names);

        $call = $this->rpc($token, 'tools/call', ['name' => 'get_ingest_config', 'arguments' => ['project' => 'Shop']]);
        $this->assertTrue($call->json('error') !== null || $call->json('result.isError') === true);
        $this->assertStringNotContainsString('lk_', (string) $call->getContent());
    }

    public function test_token_from_before_scopes_keeps_every_tool(): void
    {
        $user = User::factory()->create(['mcp_external' => true]);
        $token = $user->createToken('x', ['mcp'])->plainTextToken;

        $this->assertCount(8, $this->rpc($token, 'tools/list')->assertOk()->json('result.tools'));
    }

    public function test_read_tools_declare_output_schema_in_tools_list(): void
    {
        [$user] = $this->member('view');
        $token = $user->createToken('x', ['mcp', 'mcp:read'])->plainTextToken;

        $listed = collect($this->rpc($token, 'tools/list')->assertOk()->json('result.tools'))->keyBy('name');

        foreach (['list_projects', 'project_overview', 'new_log_groups', 'search_logs', 'get_log'] as $name) {
            $this->assertNotEmpty($listed[$name]['outputSchema']['properties'] ?? [], $name);
        }
    }

    public function test_tools_with_output_schema_return_structured_content(): void
    {
        [$user] = $this->member('view');
        $token = $user->createToken('x', ['mcp', 'mcp:read'])->plainTextToken;

        // Clients reject a tool with an outputSchema whose result has no structuredContent.
        $call = $this->rpc($token, 'tools/call', ['name' => 'list_projects', 'arguments' => []])->assertOk();
        $this->assertSame('Shop', $call->json('result.structuredContent.projects.0.name'));
        $this->assertSame($call->json('result.structuredContent'), json_decode($call->json('result.content.0.text'), true));
    }

    public function test_overview_logs_by_level_is_an_object_when_empty(): void
    {
        [$user] = $this->member('view');
        $token = $user->createToken('x', ['mcp', 'mcp:read'])->plainTextToken;

        $call = $this->rpc($token, 'tools/call', ['name' => 'project_overview', 'arguments' => ['project' => 'Shop']]);
        $this->assertSame('{}', json_encode(json_decode($call->json('result.content.0.text'))->logs_by_level));
    }

    public function test_list_projects_only_shows_visible(): void
    {
        [$user] = $this->member('view');
        Project::create(['name' => 'Hidden']);

        LoggerServer::actingAs($this->withMcpToken($user))->tool(ListProjectsTool::class)
            ->assertSee('Shop')->assertDontSee('Hidden');
    }

    public function test_search_filters_by_level_and_time(): void
    {
        [$user, $project] = $this->member('view');
        $this->log($project, 'error', 'payment gateway down');
        $this->log($project, 'info', 'cron ran');
        $this->log($project, 'error', 'ancient failure', now()->subDays(3))->forceFill(['created_at' => now()->subDays(3)])->save();

        LoggerServer::actingAs($this->withMcpToken($user))->tool(SearchLogsTool::class, ['project' => 'Shop', 'min_level' => 'error'])
            ->assertSee('payment gateway down')->assertDontSee('cron ran')->assertDontSee('ancient failure');
    }

    public function test_foreign_project_is_not_found(): void
    {
        [$user] = $this->member('view');
        $other = Project::create(['name' => 'Other']);

        LoggerServer::actingAs($this->withMcpToken($user))->tool(SearchLogsTool::class, ['project' => $other->id])
            ->assertHasErrors();
    }

    public function test_new_log_groups_only_lists_first_seen_in_window(): void
    {
        [$user, $project] = $this->member('view');
        $old = $this->log($project, 'error', 'old known error');
        $old->forceFill(['created_at' => now()->subDays(5)])->save();
        $this->log($project, 'error', 'old known error'); // recurs today, not new
        $this->log($project, 'error', 'brand new failure');

        LoggerServer::actingAs($this->withMcpToken($user))->tool(NewLogGroupsTool::class, ['project' => 'Shop', 'since' => '24h'])
            ->assertSee('brand new failure')->assertDontSee('old known error');
    }

    public function test_new_log_groups_fingerprints_across_projects_and_rejects_unknown_args(): void
    {
        [$user, $project] = $this->member('view');
        $this->log($project, 'error', 'Order 123 failed for [session_abc] id deadbeef01');
        $this->log($project, 'error', 'Order 456 failed for [session_xyz] id cafebabe99');

        LoggerServer::actingAs($this->withMcpToken($user))->tool(NewLogGroupsTool::class, ['min_level' => 'error'])
            ->assertSee('Order # failed for [session] id <hex>')->assertSee('"occurrences":2')->assertSee('"project":"all"')->assertSee('+01:00');
        LoggerServer::actingAs($this->withMcpToken($user))->tool(NewLogGroupsTool::class, ['bogus' => 1])
            ->assertHasErrors(['unknown argument bogus; allowed: project, since, min_level, limit, include_resolved']);
    }

    public function test_bench_now_freezes_clock(): void
    {
        putenv('BENCH_NOW=2026-11-21T14:15:00+01:00');
        (new \App\Providers\AppServiceProvider($this->app))->boot();
        putenv('BENCH_NOW');
        $this->assertSame('2026-11-21 13:15:00', now()->format('Y-m-d H:i:s'));
        \Carbon\Carbon::setTestNow();
        \Carbon\CarbonImmutable::setTestNow();
    }

    public function test_get_log_and_overview(): void
    {
        [$user, $project] = $this->member('view');
        $log = $this->log($project, 'critical', 'boom');

        LoggerServer::actingAs($this->withMcpToken($user))->tool(GetLogTool::class, ['project' => 'Shop', 'log_id' => $log->id])
            ->assertSee('boom');
        LoggerServer::actingAs($this->withMcpToken($user))->tool(ProjectOverviewTool::class, ['project' => 'Shop'])
            ->assertSee('critical');
    }

    public function test_ingest_config_needs_edit_permission(): void
    {
        [$viewer, $project] = $this->member('view');
        LoggerServer::actingAs($this->withMcpToken($viewer))->tool(GetIngestConfigTool::class, ['project' => 'Shop'])
            ->assertHasErrors();

        [$editor, $project2] = $this->member('edit');
        $project2->update(['name' => 'Editable']);
        LoggerServer::actingAs($this->withMcpToken($editor))->tool(GetIngestConfigTool::class, ['project' => 'Editable'])
            ->assertSee($project2->magic_key)->assertSee('/api/ingest');
    }

    public function test_create_project_is_admin_only(): void
    {
        [$user] = $this->member('edit');
        LoggerServer::actingAs($this->withMcpToken($user))->tool(CreateProjectTool::class, ['name' => 'New'])
            ->assertHasErrors();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        LoggerServer::actingAs($this->withMcpToken($admin))->tool(CreateProjectTool::class, ['name' => 'New'])
            ->assertSee('get_ingest_config');
        $this->assertDatabaseHas('projects', ['name' => 'New']);
        $this->assertTrue(Project::where('name', 'New')->first()->users()->whereKey($admin->id)->exists());
    }

    public function test_resolve_log_group_hides_group_and_recurrence_shows_as_new(): void
    {
        [$user, $project] = $this->member('edit');
        $first = $this->log($project, 'error', 'SQLSTATE 1140 GROUP BY');
        $this->log($project, 'error', 'SQLSTATE 1140 GROUP BY');
        $this->log($project, 'warning', 'SQLSTATE 1140 GROUP BY'); // other level, other group
        $this->log($project, 'error', 'unrelated failure');

        LoggerServer::actingAs($this->withMcpToken($user))->tool(ResolveLogGroupTool::class, ['project' => 'Shop', 'log_id' => $first->id, 'note' => 'fixed in abc123'])
            ->assertSee('"logs_affected":2');

        $this->assertSame(2, Log::whereNotNull('resolved_at')->where('resolution_note', 'fixed in abc123')->where('resolved_by', $user->id)->count());

        LoggerServer::actingAs($this->withMcpToken($user))->tool(SearchLogsTool::class, ['project' => 'Shop', 'min_level' => 'error'])
            ->assertSee('unrelated failure')->assertDontSee('1140');
        LoggerServer::actingAs($this->withMcpToken($user))->tool(SearchLogsTool::class, ['project' => 'Shop', 'min_level' => 'error', 'include_resolved' => true])
            ->assertSee('1140');
        LoggerServer::actingAs($this->withMcpToken($user))->tool(NewLogGroupsTool::class, ['project' => 'Shop', 'min_level' => 'error'])
            ->assertDontSee('1140');

        $this->log($project, 'error', 'SQLSTATE 1140 GROUP BY'); // regression
        LoggerServer::actingAs($this->withMcpToken($user))->tool(NewLogGroupsTool::class, ['project' => 'Shop', 'min_level' => 'error'])
            ->assertSee('1140');

        LoggerServer::actingAs($this->withMcpToken($user))->tool(ResolveLogGroupTool::class, ['project' => 'Shop', 'log_id' => $first->id, 'reopen' => true])
            ->assertSee('"logs_affected":2');
        $this->assertSame(0, Log::whereNotNull('resolved_at')->count());
    }

    public function test_resolve_log_group_needs_edit_permission(): void
    {
        [$user, $project] = $this->member('view');
        $log = $this->log($project, 'error', 'boom');

        LoggerServer::actingAs($this->withMcpToken($user))->tool(ResolveLogGroupTool::class, ['project' => 'Shop', 'log_id' => $log->id])
            ->assertHasErrors();
        $this->assertNull($log->fresh()->resolved_at);
    }
}
