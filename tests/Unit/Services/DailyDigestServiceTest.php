<?php

namespace Tests\Unit\Services;

use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use App\Services\DailyDigestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDigestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2023-01-02 12:00:00');
    }

    public function test_gather_data_returns_empty_arrays_when_no_settings_enabled(): void
    {
        $user = User::factory()->create([
            'daily_digest_settings' => [],
        ]);
        $service = new DailyDigestService;

        $data = $service->gatherData($user);

        $this->assertEmpty($data['logs_summary']);
        $this->assertEmpty($data['memory_alerts']);
        $this->assertEmpty($data['storage_alerts']);
    }

    public function test_gather_data_includes_logs_summary_when_enabled(): void
    {
        $user = User::factory()->create([
            'daily_digest_settings' => ['logs' => true],
        ]);

        // Ensure settings are persisted correctly
        $this->assertTrue($user->daily_digest_settings['logs']);

        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['permission' => Project::PERMISSION_VIEW]);
        Log::factory()->count(3)->create(['project_id' => $project->id, 'level' => 'error']);
        Log::factory()->count(2)->create(['project_id' => $project->id, 'level' => 'info']);

        $service = new DailyDigestService;
        $data = $service->gatherData($user);

        $this->assertCount(1, $data['logs_summary']);
        $this->assertEquals($project->name, $data['logs_summary'][0]['name']);
        $this->assertEquals(3, $data['logs_summary'][0]['counts']['error']);
        $this->assertEquals(2, $data['logs_summary'][0]['counts']['info']);
    }

    public function test_gather_data_includes_memory_alerts_when_threshold_exceeded(): void
    {
        $user = User::factory()->create([
            'daily_digest_settings' => ['memory_usage' => true],
        ]);

        // Normal project
        $normalProject = Project::factory()->create([
            'server_stats' => ['system' => ['server_memory' => ['percent_used' => 50]]],
        ]);

        // High memory project
        $highMemProject = Project::factory()->create([
            'server_stats' => ['system' => ['server_memory' => ['percent_used' => 85.5]]],
        ]);
        $normalProject->users()->attach($user->id, ['permission' => Project::PERMISSION_VIEW]);
        $highMemProject->users()->attach($user->id, ['permission' => Project::PERMISSION_VIEW]);

        $service = new DailyDigestService;
        $data = $service->gatherData($user);

        $this->assertCount(1, $data['memory_alerts']);
        $this->assertEquals($highMemProject->name, $data['memory_alerts'][0]['project']);
        $this->assertEquals(85.5, $data['memory_alerts'][0]['usage']);
    }

    public function test_gather_data_includes_storage_alerts_when_threshold_exceeded(): void
    {
        $user = User::factory()->create([
            'daily_digest_settings' => ['filesize' => true],
        ]);

        // Normal project
        $normalProject = Project::factory()->create([
            'server_stats' => ['system' => ['disk_space' => ['percent_used' => 40]]],
        ]);

        // High storage project
        $highStorageProject = Project::factory()->create([
            'server_stats' => ['system' => ['disk_space' => ['percent_used' => 90.2]]],
        ]);
        $normalProject->users()->attach($user->id, ['permission' => Project::PERMISSION_VIEW]);
        $highStorageProject->users()->attach($user->id, ['permission' => Project::PERMISSION_VIEW]);

        $service = new DailyDigestService;
        $data = $service->gatherData($user);

        $this->assertCount(1, $data['storage_alerts']);
        $this->assertEquals($highStorageProject->name, $data['storage_alerts'][0]['project']);
        $this->assertEquals(90.2, $data['storage_alerts'][0]['usage']);
    }
}
