<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogExplorerMethodFilterTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): array
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $project = Project::create(['name' => 'Shop']);
        $project->users()->attach($user->id, ['permission' => 'view']);

        return [$user, $project];
    }

    private function logWithRawContext(Project $project, string $message, string $context): void
    {
        $log = Log::create(['project_id' => $project->id, 'level' => 'error', 'message' => $message, 'logged_at' => now()]);
        DB::table('logs')->where('id', $log->id)->update(['context' => $context]);
    }

    public function test_method_filter_matches_compact_and_spaced_json(): void
    {
        [$user, $project] = $this->viewer();
        $this->logWithRawContext($project, 'compact log', '{"trace":[{"function":"store","class":"App\\\\Http\\\\Controllers\\\\OrderController"}]}');
        $this->logWithRawContext($project, 'spaced log', '{"trace": [{"function": "store"}]}');
        $this->logWithRawContext($project, 'other log', '{"trace":[{"function":"index"}]}');

        $this->actingAs($user)->get("/projects/{$project->id}/logs?method=store")
            ->assertOk()->assertSee('compact log')->assertSee('spaced log')->assertDontSee('other log');
    }

    public function test_method_filter_treats_like_wildcards_literally(): void
    {
        [$user, $project] = $this->viewer();
        $this->logWithRawContext($project, 'abc log', '{"trace":[{"function":"abc"}]}');
        $this->logWithRawContext($project, 'underscore log', '{"trace":[{"function":"a_c"}]}');

        $this->actingAs($user)->get("/projects/{$project->id}/logs?method=a_c")
            ->assertOk()->assertSee('underscore log')->assertDontSee('abc log');
        $this->actingAs($user)->get('/projects/'.$project->id.'/logs?method='.urlencode('%'))
            ->assertOk()->assertDontSee('abc log')->assertDontSee('underscore log');
    }

    public function test_unknown_method_lists_logs_without_function(): void
    {
        [$user, $project] = $this->viewer();
        $this->logWithRawContext($project, 'traced log', '{"trace":[{"function":"store"}]}');
        $this->logWithRawContext($project, 'plain log', '{"foo":"bar"}');

        $this->actingAs($user)->get("/projects/{$project->id}/logs?method=unknown")
            ->assertOk()->assertSee('plain log')->assertDontSee('traced log');
    }
}
