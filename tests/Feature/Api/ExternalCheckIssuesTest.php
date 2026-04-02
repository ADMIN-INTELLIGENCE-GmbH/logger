<?php

namespace Tests\Feature\Api;

use App\Models\ExternalCheck;
use App\Models\Log;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalCheckIssuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_check_requires_bearer_token(): void
    {
        $user = User::factory()->create();
        $check = ExternalCheck::factory()
            ->for($user)
            ->withToken('secret-token')
            ->create();

        $response = $this->getJson(route('api.external-checks.issues', $check->slug));

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
            ]);
    }

    public function test_external_check_auto_includes_new_tagged_projects(): void
    {
        $user = User::factory()->create();
        $tag = Tag::create(['name' => 'production']);
        $check = ExternalCheck::factory()
            ->for($user)
            ->withToken('secret-token')
            ->create([
                'selector_tags' => ['production'],
                'count_threshold' => 3,
                'time_window_minutes' => 60,
                'min_level' => 'error',
                'group_across_projects' => true,
            ]);

        $projectOne = $this->makeVisibleProject($user, 'Alpha');
        $projectTwo = $this->makeVisibleProject($user, 'Beta');

        $projectOne->tags()->attach($tag->id);
        $projectTwo->tags()->attach($tag->id);

        $newProject = $this->makeVisibleProject($user, 'Gamma');
        $newProject->tags()->attach($tag->id);

        foreach ([$projectOne, $projectTwo, $newProject] as $project) {
            Log::factory()->forProject($project)->create([
                'level' => 'error',
                'message' => 'Database connection failed',
                'controller' => 'App\\Http\\Controllers\\HealthController',
                'route_name' => 'health.check',
                'created_at' => now()->subMinutes(5),
            ]);
        }

        $response = $this->withToken('secret-token')
            ->getJson(route('api.external-checks.issues', $check->slug));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.matched_projects_count', 3)
            ->assertJsonPath('data.issue_count', 1)
            ->assertJsonPath('data.issues.0.type', 'log_group')
            ->assertJsonPath('data.issues.0.count', 3)
            ->assertJsonPath('data.issues.0.group_across_projects', true);

        $this->assertCount(3, $response->json('data.issues.0.affected_projects'));
    }

    public function test_external_check_groups_by_project_by_default(): void
    {
        $user = User::factory()->create();
        $tag = Tag::create(['name' => 'production']);
        $check = ExternalCheck::factory()
            ->for($user)
            ->withToken('secret-token')
            ->create([
                'selector_tags' => ['production'],
                'count_threshold' => 1,
                'time_window_minutes' => 60,
                'min_level' => 'error',
                'group_across_projects' => false,
            ]);

        $projectOne = $this->makeVisibleProject($user, 'Alpha');
        $projectTwo = $this->makeVisibleProject($user, 'Beta');
        $projectOne->tags()->attach($tag->id);
        $projectTwo->tags()->attach($tag->id);

        foreach ([$projectOne, $projectTwo] as $project) {
            Log::factory()->forProject($project)->create([
                'level' => 'error',
                'message' => 'Database connection failed',
                'controller' => 'App\\Http\\Controllers\\HealthController',
                'route_name' => 'health.check',
                'created_at' => now()->subMinutes(5),
            ]);
        }

        $response = $this->withToken('secret-token')
            ->getJson(route('api.external-checks.issues', $check->slug));

        $response->assertOk()
            ->assertJsonPath('data.issue_count', 2)
            ->assertJsonPath('data.issues.0.group_across_projects', false)
            ->assertJsonPath('data.issues.1.group_across_projects', false);

        $this->assertCount(1, $response->json('data.issues.0.affected_projects'));
        $this->assertCount(1, $response->json('data.issues.1.affected_projects'));
    }

    public function test_external_check_respects_excluded_projects_and_resource_thresholds(): void
    {
        $user = User::factory()->create();
        $tag = Tag::create(['name' => 'production']);
        $projectOne = $this->makeVisibleProject($user, 'Alpha', [
            'server_stats' => [
                'system' => [
                    'server_memory' => ['percent_used' => 88.4],
                ],
            ],
            'last_server_stats_at' => now(),
        ]);
        $projectTwo = $this->makeVisibleProject($user, 'Beta', [
            'server_stats' => [
                'system' => [
                    'server_memory' => ['percent_used' => 96.1],
                ],
            ],
            'last_server_stats_at' => now(),
        ]);
        $projectOne->tags()->attach($tag->id);
        $projectTwo->tags()->attach($tag->id);

        $check = ExternalCheck::factory()
            ->for($user)
            ->withToken('secret-token')
            ->create([
                'selector_tags' => ['production'],
                'excluded_project_ids' => [$projectTwo->id],
                'count_threshold' => 1,
                'memory_percent_gte' => 80,
            ]);

        $response = $this->withToken('secret-token')
            ->getJson(route('api.external-checks.issues', $check->slug));

        $response->assertOk()
            ->assertJsonPath('data.matched_projects_count', 1)
            ->assertJsonPath('data.issue_count', 1)
            ->assertJsonPath('data.issues.0.type', 'resource')
            ->assertJsonPath('data.issues.0.resource', 'memory')
            ->assertJsonPath('data.issues.0.affected_projects.0.id', $projectOne->id);
    }

    protected function makeVisibleProject(User $user, string $name, array $attributes = []): Project
    {
        $project = Project::factory()->create(array_merge([
            'name' => $name,
        ], $attributes));

        $project->users()->attach($user->id, ['permission' => Project::PERMISSION_EDIT]);

        return $project;
    }
}
