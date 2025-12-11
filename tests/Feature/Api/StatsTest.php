<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_push_stats()
    {
        $project = Project::factory()->create([
            'is_active' => true,
        ]);

        $stats = [
            'cpu_usage' => '15%',
            'memory_usage' => '512MB',
            'disk_free' => '20GB',
            'jobs_in_queue' => 5,
        ];

        $response = $this->postJson(route('api.stats'), $stats, [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);

        $project->refresh();
        $this->assertEquals($stats, $project->server_stats);
        $this->assertNotNull($project->last_server_stats_at);
    }

    public function test_cannot_push_stats_with_invalid_key()
    {
        $response = $this->postJson(route('api.stats'), [], [
            'X-Project-Key' => 'invalid-key',
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_push_stats_to_inactive_project()
    {
        $project = Project::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson(route('api.stats'), [], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(401);
    }
}
