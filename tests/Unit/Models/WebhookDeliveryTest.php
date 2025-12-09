<?php

namespace Tests\Unit\Models;

use App\Models\Log;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_delivery_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);
        
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
        ]);

        $this->assertInstanceOf(Project::class, $delivery->project);
        $this->assertEquals($project->id, $delivery->project->id);
    }

    public function test_webhook_delivery_belongs_to_log(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);
        
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
        ]);

        $this->assertInstanceOf(Log::class, $delivery->log);
        $this->assertEquals($log->id, $delivery->log->id);
    }

    public function test_webhook_delivery_can_have_null_log_id(): void
    {
        $project = Project::factory()->create();
        
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => null,
            'event_type' => 'test',
        ]);

        $this->assertNull($delivery->log_id);
        $this->assertNull($delivery->log);
    }

    public function test_webhook_delivery_casts_payload_to_array(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);
        
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
            'payload' => ['text' => 'Test message', 'attachments' => []],
        ]);

        $this->assertIsArray($delivery->payload);
        $this->assertEquals('Test message', $delivery->payload['text']);
    }

    public function test_webhook_delivery_casts_success_to_boolean(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);
        
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
            'success' => true,
        ]);

        $this->assertTrue($delivery->success);
    }

    public function test_scope_recent_orders_by_created_at_desc(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);
        
        $oldDelivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
            'created_at' => now()->subHour(),
        ]);
        
        $newDelivery = WebhookDelivery::factory()->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
            'created_at' => now(),
        ]);

        $recent = WebhookDelivery::recent()->get();

        $this->assertEquals($newDelivery->id, $recent->first()->id);
        $this->assertEquals($oldDelivery->id, $recent->last()->id);
    }

    public function test_scope_recent_limits_results(): void
    {
        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);
        
        WebhookDelivery::factory()->count(10)->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
        ]);

        $recent = WebhookDelivery::recent(5)->get();

        $this->assertCount(5, $recent);
    }
}
