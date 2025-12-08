<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_logs_deletes_old_logs(): void
    {
        $project = Project::factory()->create(['retention_days' => 7]);

        // Create old logs (should be deleted)
        Log::factory()
            ->count(5)
            ->forProject($project)
            ->create(['created_at' => now()->subDays(10)]);

        // Create recent logs (should be kept)
        Log::factory()
            ->count(3)
            ->forProject($project)
            ->create(['created_at' => now()->subDays(2)]);

        $this->assertDatabaseCount('logs', 8);

        $this->artisan('app:prune-logs')
            ->assertExitCode(0);

        $this->assertDatabaseCount('logs', 3);
    }

    public function test_prune_logs_respects_infinite_retention(): void
    {
        $project = Project::factory()->infiniteRetention()->create();

        Log::factory()
            ->count(5)
            ->forProject($project)
            ->create(['created_at' => now()->subDays(365)]);

        $this->artisan('app:prune-logs')
            ->assertExitCode(0);

        $this->assertDatabaseCount('logs', 5);
    }

    public function test_prune_logs_dry_run_does_not_delete(): void
    {
        $project = Project::factory()->create(['retention_days' => 7]);

        Log::factory()
            ->count(5)
            ->forProject($project)
            ->create(['created_at' => now()->subDays(10)]);

        $this->artisan('app:prune-logs', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('logs', 5);
    }
}
