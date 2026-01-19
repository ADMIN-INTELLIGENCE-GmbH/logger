<?php

namespace Tests\Unit\Models;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_has_correct_levels(): void
    {
        $expectedLevels = [
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ];

        $this->assertEquals($expectedLevels, Log::LEVELS);
    }

    public function test_log_level_severity_order(): void
    {
        $this->assertEquals(0, Log::LEVEL_SEVERITY['debug']);
        $this->assertEquals(1, Log::LEVEL_SEVERITY['info']);
        $this->assertEquals(2, Log::LEVEL_SEVERITY['notice']);
        $this->assertEquals(3, Log::LEVEL_SEVERITY['warning']);
        $this->assertEquals(4, Log::LEVEL_SEVERITY['error']);
        $this->assertEquals(5, Log::LEVEL_SEVERITY['critical']);
        $this->assertEquals(6, Log::LEVEL_SEVERITY['alert']);
        $this->assertEquals(7, Log::LEVEL_SEVERITY['emergency']);
    }

    public function test_meets_threshold_returns_true_when_log_meets_threshold(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
        ]);

        $this->assertTrue($log->meetsThreshold('error'));
        $this->assertTrue($log->meetsThreshold('warning'));
        $this->assertTrue($log->meetsThreshold('debug'));
    }

    public function test_meets_threshold_returns_false_when_log_below_threshold(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'warning',
        ]);

        $this->assertFalse($log->meetsThreshold('error'));
        $this->assertFalse($log->meetsThreshold('critical'));
        $this->assertFalse($log->meetsThreshold('emergency'));
    }

    public function test_log_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $log->project);
        $this->assertEquals($project->id, $log->project->id);
    }

    public function test_log_casts_context_to_array(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'context' => ['key' => 'value', 'nested' => ['foo' => 'bar']],
        ]);

        $this->assertIsArray($log->context);
        $this->assertEquals('value', $log->context['key']);
        $this->assertEquals('bar', $log->context['nested']['foo']);
    }

    public function test_log_casts_extra_to_array(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'extra' => ['monolog_data' => 'test'],
        ]);

        $this->assertIsArray($log->extra);
        $this->assertEquals('test', $log->extra['monolog_data']);
    }

    public function test_log_casts_app_debug_to_boolean(): void
    {
        $project = Project::factory()->create();

        $log1 = Log::factory()->create([
            'project_id' => $project->id,
            'app_debug' => true,
        ]);

        $log2 = Log::factory()->create([
            'project_id' => $project->id,
            'app_debug' => false,
        ]);

        $this->assertTrue($log1->app_debug);
        $this->assertFalse($log2->app_debug);
    }

    public function test_scope_level_filters_by_level(): void
    {
        $project = Project::factory()->create();

        Log::factory()->count(3)->create([
            'project_id' => $project->id,
            'level' => 'error',
        ]);

        Log::factory()->count(2)->create([
            'project_id' => $project->id,
            'level' => 'info',
        ]);

        $this->assertEquals(3, Log::level('error')->count());
        $this->assertEquals(2, Log::level('info')->count());
    }

    public function test_scope_controller_filters_by_controller(): void
    {
        $project = Project::factory()->create();

        Log::factory()->create([
            'project_id' => $project->id,
            'controller' => 'App\\Http\\Controllers\\UserController',
        ]);

        Log::factory()->create([
            'project_id' => $project->id,
            'controller' => 'App\\Http\\Controllers\\OrderController',
        ]);

        $this->assertEquals(1, Log::controller('App\\Http\\Controllers\\UserController')->count());
    }

    public function test_scope_for_user_filters_by_user_id(): void
    {
        $project = Project::factory()->create();

        Log::factory()->count(2)->create([
            'project_id' => $project->id,
            'user_id' => 'user-123',
        ]);

        Log::factory()->create([
            'project_id' => $project->id,
            'user_id' => 'user-456',
        ]);

        $this->assertEquals(2, Log::forUser('user-123')->count());
    }

    public function test_scope_search_message_finds_matching_logs(): void
    {
        $project = Project::factory()->create();

        Log::factory()->create([
            'project_id' => $project->id,
            'message' => 'Database connection failed',
        ]);

        Log::factory()->create([
            'project_id' => $project->id,
            'message' => 'User login successful',
        ]);

        $this->assertEquals(1, Log::searchMessage('Database')->count());
        $this->assertEquals(1, Log::searchMessage('login')->count());
        $this->assertEquals(0, Log::searchMessage('payment')->count());
    }

    public function test_scope_created_between_filters_by_date_range(): void
    {
        $project = Project::factory()->create();

        Log::factory()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDays(5),
        ]);

        Log::factory()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDays(2),
        ]);

        Log::factory()->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);

        $this->assertEquals(2, Log::createdBetween(now()->subDays(3), now())->count());
    }

    public function test_user_id_accessor_falls_back_to_context(): void
    {
        $project = Project::factory()->create();

        // Case 1: user_id provided in column
        $logWithColumn = Log::factory()->create([
            'project_id' => $project->id,
            'user_id' => '123',
            'context' => ['user_id' => '456'], // Should be ignored in favor of column
        ]);

        $this->assertEquals('123', $logWithColumn->user_id);

        // Case 2: user_id missing in column, present in context
        $logWithContext = Log::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'context' => ['user_id' => '456'],
        ]);

        $this->assertEquals('456', $logWithContext->user_id);

        // Case 3: user_id missing in both
        $logEmpty = Log::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'context' => ['foo' => 'bar'],
        ]);

        $this->assertNull($logEmpty->user_id);
    }
}
