<?php

namespace Tests\Feature;

use App\Mcp\Servers\LoggerServer;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\GetIngestConfigTool;
use App\Mcp\Tools\GetLogTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\NewLogGroupsTool;
use App\Mcp\Tools\ProjectOverviewTool;
use App\Mcp\Tools\SearchLogsTool;
use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $permission): array
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $project = Project::create(['name' => 'Shop']);
        $project->users()->attach($user->id, ['permission' => $permission]);

        return [$user, $project];
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
        $user = User::factory()->create();
        $token = $user->createToken('x', ['mcp'])->plainTextToken;

        $this->withToken($token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-03-26', 'capabilities' => (object) [], 'clientInfo' => ['name' => 't', 'version' => '1']],
        ])->assertOk()->assertJsonPath('result.serverInfo.name', 'Logger');
    }

    public function test_list_projects_only_shows_visible(): void
    {
        [$user] = $this->member('view');
        Project::create(['name' => 'Hidden']);

        LoggerServer::actingAs($user)->tool(ListProjectsTool::class)
            ->assertSee('Shop')->assertDontSee('Hidden');
    }

    public function test_search_filters_by_level_and_time(): void
    {
        [$user, $project] = $this->member('view');
        $this->log($project, 'error', 'payment gateway down');
        $this->log($project, 'info', 'cron ran');
        $this->log($project, 'error', 'ancient failure', now()->subDays(3))->forceFill(['created_at' => now()->subDays(3)])->save();

        LoggerServer::actingAs($user)->tool(SearchLogsTool::class, ['project' => 'Shop', 'min_level' => 'error'])
            ->assertSee('payment gateway down')->assertDontSee('cron ran')->assertDontSee('ancient failure');
    }

    public function test_foreign_project_is_not_found(): void
    {
        [$user] = $this->member('view');
        $other = Project::create(['name' => 'Other']);

        LoggerServer::actingAs($user)->tool(SearchLogsTool::class, ['project' => $other->id])
            ->assertHasErrors();
    }

    public function test_new_log_groups_only_lists_first_seen_in_window(): void
    {
        [$user, $project] = $this->member('view');
        $old = $this->log($project, 'error', 'old known error');
        $old->forceFill(['created_at' => now()->subDays(5)])->save();
        $this->log($project, 'error', 'old known error'); // recurs today, not new
        $this->log($project, 'error', 'brand new failure');

        LoggerServer::actingAs($user)->tool(NewLogGroupsTool::class, ['project' => 'Shop', 'since' => '24h'])
            ->assertSee('brand new failure')->assertDontSee('old known error');
    }

    public function test_get_log_and_overview(): void
    {
        [$user, $project] = $this->member('view');
        $log = $this->log($project, 'critical', 'boom');

        LoggerServer::actingAs($user)->tool(GetLogTool::class, ['project' => 'Shop', 'log_id' => $log->id])
            ->assertSee('boom');
        LoggerServer::actingAs($user)->tool(ProjectOverviewTool::class, ['project' => 'Shop'])
            ->assertSee('critical');
    }

    public function test_ingest_config_needs_edit_permission(): void
    {
        [$viewer, $project] = $this->member('view');
        LoggerServer::actingAs($viewer)->tool(GetIngestConfigTool::class, ['project' => 'Shop'])
            ->assertHasErrors();

        [$editor, $project2] = $this->member('edit');
        $project2->update(['name' => 'Editable']);
        LoggerServer::actingAs($editor)->tool(GetIngestConfigTool::class, ['project' => 'Editable'])
            ->assertSee($project2->magic_key)->assertSee('/api/ingest');
    }

    public function test_create_project_is_admin_only(): void
    {
        [$user] = $this->member('edit');
        LoggerServer::actingAs($user)->tool(CreateProjectTool::class, ['name' => 'New'])
            ->assertHasErrors();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        LoggerServer::actingAs($admin)->tool(CreateProjectTool::class, ['name' => 'New'])
            ->assertSee('get_ingest_config');
        $this->assertDatabaseHas('projects', ['name' => 'New']);
        $this->assertTrue(Project::where('name', 'New')->first()->users()->whereKey($admin->id)->exists());
    }

    public function test_profile_can_create_and_revoke_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/tokens', ['token_name' => 'mac', 'token_days' => 90])
            ->assertRedirect('/profile')->assertSessionHas('new_token');
        $this->assertCount(1, $user->tokens);
        $this->assertSame(['mcp'], $user->tokens->first()->abilities);

        $this->actingAs($user)->delete('/profile/tokens/'.$user->tokens->first()->id)->assertRedirect('/profile');
        $this->assertCount(0, $user->fresh()->tokens);
    }
}
