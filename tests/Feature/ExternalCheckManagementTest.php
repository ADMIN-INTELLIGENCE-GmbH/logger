<?php

namespace Tests\Feature;

use App\Models\ExternalCheck;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalCheckManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_external_check(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['permission' => Project::PERMISSION_EDIT]);

        $response = $this->actingAs($user)->post(route('checks.store'), [
            'name' => 'Production API Errors',
            'description' => 'Track important production issues',
            'enabled' => '1',
            'min_level' => 'error',
            'time_window_minutes' => 30,
            'count_threshold' => 3,
            'group_by' => ['message', 'controller', 'route_name', 'level'],
            'group_across_projects' => '1',
            'selector_tags' => 'production, api',
            'included_project_ids' => [$project->id],
            'excluded_project_ids' => [],
            'memory_percent_gte' => '85',
            'disk_percent_gte' => '90',
        ]);

        $response->assertRedirect(route('checks.index'));
        $response->assertSessionHas('generated_token');

        $check = ExternalCheck::first();

        $this->assertNotNull($check);
        $this->assertSame($user->id, $check->user_id);
        $this->assertSame(['production', 'api'], $check->selector_tags);
        $this->assertSame([$project->id], $check->included_project_ids);
        $this->assertSame('error', $check->min_level);
        $this->assertTrue($check->group_across_projects);
        $this->assertNotNull($check->encrypted_token);
        $this->assertSame(substr(session('generated_token'), -8), $check->token_last_eight);
        $this->assertSame(session('generated_token'), $check->plainTextToken());
    }

    public function test_user_cannot_include_project_they_cannot_view(): void
    {
        $user = User::factory()->create();
        $hiddenProject = Project::factory()->create();

        $response = $this->from(route('checks.index'))
            ->actingAs($user)
            ->post(route('checks.store'), [
                'name' => 'Hidden Project Check',
                'enabled' => '1',
                'min_level' => 'error',
                'time_window_minutes' => 30,
                'count_threshold' => 3,
                'group_by' => ['message'],
                'selector_tags' => '',
                'included_project_ids' => [$hiddenProject->id],
                'excluded_project_ids' => [],
            ]);

        $response->assertRedirect(route('checks.index'));
        $response->assertSessionHasErrors(['included_project_ids.0']);
        $this->assertDatabaseCount('external_checks', 0);
    }

    public function test_user_can_rotate_external_check_token(): void
    {
        $user = User::factory()->create();
        $check = ExternalCheck::factory()
            ->for($user)
            ->withToken('old-token-value')
            ->create();
        $oldHash = $check->token_hash;

        $response = $this->actingAs($user)
            ->post(route('checks.rotate-token', $check));

        $response->assertRedirect(route('checks.index'));
        $response->assertSessionHas('generated_token');

        $check->refresh();

        $this->assertNotSame($oldHash, $check->token_hash);
        $this->assertSame(session('generated_token'), $check->plainTextToken());
    }
}
