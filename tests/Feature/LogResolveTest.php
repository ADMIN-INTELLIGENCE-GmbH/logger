<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogResolveTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $permission): array
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $project = Project::create(['name' => 'Shop']);
        $project->users()->attach($user->id, ['permission' => $permission]);

        return [$user, $project];
    }

    private function log(Project $project, string $level, string $message): Log
    {
        return Log::create([
            'project_id' => $project->id,
            'level' => $level,
            'message' => $message,
            'logged_at' => now(),
        ]);
    }

    public function test_explorer_hides_resolved_by_default_and_filters_by_status(): void
    {
        [$user, $project] = $this->member('view');
        $this->log($project, 'error', 'still broken');
        $this->log($project, 'error', 'already fixed')->forceFill(['resolved_at' => now()])->save();

        $this->actingAs($user)->get("/projects/{$project->id}/logs")
            ->assertOk()->assertSee('still broken')->assertDontSee('already fixed');
        $this->actingAs($user)->get("/projects/{$project->id}/logs?status=resolved")
            ->assertOk()->assertSee('already fixed')->assertDontSee('still broken');
        $this->actingAs($user)->get("/projects/{$project->id}/logs?status=all")
            ->assertOk()->assertSee('already fixed')->assertSee('still broken');
    }

    public function test_bulk_resolve_and_reopen_selected_logs(): void
    {
        [$user, $project] = $this->member('edit');
        $a = $this->log($project, 'error', 'a');
        $b = $this->log($project, 'error', 'b');
        $other = Project::create(['name' => 'Other']);
        $foreign = $this->log($other, 'error', 'foreign');

        $this->actingAs($user)->postJson("/projects/{$project->id}/logs/bulk-resolve", ['log_ids' => [$a->id, $foreign->id], 'note' => 'deployed fix'])
            ->assertOk()->assertJsonPath('count', 1);

        $this->assertNotNull($a->fresh()->resolved_at);
        $this->assertSame($user->id, $a->fresh()->resolved_by);
        $this->assertSame('deployed fix', $a->fresh()->resolution_note);
        $this->assertNull($b->fresh()->resolved_at);
        $this->assertNull($foreign->fresh()->resolved_at);

        $this->actingAs($user)->postJson("/projects/{$project->id}/logs/bulk-resolve", ['log_ids' => [$a->id], 'reopen' => true])
            ->assertOk()->assertJsonPath('count', 1);
        $this->assertNull($a->fresh()->resolved_at);
    }

    public function test_resolve_group_marks_same_level_and_message(): void
    {
        [$user, $project] = $this->member('edit');
        $sample = $this->log($project, 'error', 'SQLSTATE 1140');
        $this->log($project, 'error', 'SQLSTATE 1140');
        $warning = $this->log($project, 'warning', 'SQLSTATE 1140');

        $this->actingAs($user)->postJson("/projects/{$project->id}/logs/{$sample->id}/resolve-group", ['note' => 'fixed'])
            ->assertOk()->assertJsonPath('count', 2);

        $this->assertNull($warning->fresh()->resolved_at);
        $this->assertSame('fixed', $sample->fresh()->resolution_note);
    }

    public function test_resolve_needs_edit_permission(): void
    {
        [$user, $project] = $this->member('view');
        $log = $this->log($project, 'error', 'boom');

        $this->actingAs($user)->postJson("/projects/{$project->id}/logs/bulk-resolve", ['log_ids' => [$log->id]])
            ->assertForbidden();
        $this->actingAs($user)->postJson("/projects/{$project->id}/logs/{$log->id}/resolve-group")
            ->assertForbidden();
        $this->assertNull($log->fresh()->resolved_at);
    }
}
