<?php

namespace Tests\Unit\Listeners;

use App\Events\LogCreated;
use App\Listeners\WebhookDispatcher;
use App\Models\Log;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        
        // Prevent model events from auto-dispatching webhooks during test setup
        Event::fake([LogCreated::class]);
    }

    public function test_webhook_not_sent_when_project_has_no_webhook(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => null,
            'webhook_enabled' => true,
        ]);
        
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
        ]);

        Http::fake();
        Event::fake([LogCreated::class]); // Re-fake after log creation

        $dispatcher = new WebhookDispatcher();
        $dispatcher->handle(new LogCreated($log));

        Http::assertNothingSent();
        $this->assertEquals(0, WebhookDelivery::count());
    }

    public function test_webhook_not_sent_when_webhook_disabled(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => false,
        ]);
        
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
        ]);

        Http::fake();

        $dispatcher = new WebhookDispatcher();
        $dispatcher->handle(new LogCreated($log));

        Http::assertNothingSent();
    }

    public function test_webhook_not_sent_when_log_below_threshold(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => true,
            'webhook_threshold' => 'error',
        ]);
        
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'info', // Below error threshold
        ]);

        Http::fake();

        $dispatcher = new WebhookDispatcher();
        $dispatcher->handle(new LogCreated($log));

        Http::assertNothingSent();
    }

    public function test_webhook_sent_when_log_meets_threshold(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => true,
            'webhook_threshold' => 'error',
            'webhook_format' => 'slack',
        ]);
        
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
            'message' => 'Test error message',
        ]);

        Http::fake([
            'hooks.slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        $dispatcher = new WebhookDispatcher();
        $dispatcher->handle(new LogCreated($log));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/test';
        });

        $this->assertEquals(1, WebhookDelivery::count());
        
        $delivery = WebhookDelivery::first();
        $this->assertTrue($delivery->success);
        $this->assertEquals(200, $delivery->status_code);
        $this->assertEquals('log', $delivery->event_type);
    }

    public function test_webhook_delivery_records_failure(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => true,
            'webhook_threshold' => 'error',
            'webhook_format' => 'slack',
        ]);
        
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
        ]);

        Http::fake([
            'hooks.slack.com/*' => Http::response(['error' => 'invalid_token'], 401),
        ]);

        $dispatcher = new WebhookDispatcher();
        $dispatcher->handle(new LogCreated($log));

        $delivery = WebhookDelivery::first();
        $this->assertFalse($delivery->success);
        $this->assertEquals(401, $delivery->status_code);
    }

    public function test_rate_limiting_prevents_webhook_spam(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => true,
            'webhook_threshold' => 'error',
            'webhook_format' => 'slack',
        ]);

        Http::fake([
            'hooks.slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        $dispatcher = new WebhookDispatcher();

        // Simulate hitting rate limit (30 per minute)
        for ($i = 0; $i < 35; $i++) {
            $log = Log::factory()->create([
                'project_id' => $project->id,
                'level' => 'error',
            ]);
            $dispatcher->handle(new LogCreated($log));
        }

        // Should have sent only 30 webhooks due to rate limit
        $this->assertEquals(30, WebhookDelivery::count());
    }

    public function test_test_webhook_creates_delivery_record(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => true,
            'webhook_format' => 'slack',
        ]);

        Http::fake([
            'hooks.slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        $delivery = WebhookDispatcher::sendTestWebhook($project);

        $this->assertInstanceOf(WebhookDelivery::class, $delivery);
        $this->assertEquals('test', $delivery->event_type);
        $this->assertNull($delivery->log_id);
        $this->assertTrue($delivery->success);
    }

    public function test_webhook_uses_correct_format_for_discord(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://discord.com/api/webhooks/test',
            'webhook_enabled' => true,
            'webhook_threshold' => 'error',
            'webhook_format' => 'discord',
        ]);
        
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
            'message' => 'Test error',
        ]);

        Http::fake([
            'discord.com/*' => Http::response([], 204),
        ]);

        $dispatcher = new WebhookDispatcher();
        $dispatcher->handle(new LogCreated($log));

        Http::assertSent(function ($request) {
            $payload = $request->data();
            // Discord uses 'embeds' instead of 'attachments'
            return isset($payload['embeds']) || isset($payload['content']);
        });
    }
}
