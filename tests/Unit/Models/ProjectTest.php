<?php

namespace Tests\Unit\Models;

use App\Models\Log;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_has_correct_webhook_formats(): void
    {
        $expectedFormats = [
            'slack' => 'Slack',
            'mattermost' => 'Mattermost',
            'discord' => 'Discord',
            'teams' => 'Microsoft Teams',
            'generic' => 'Generic JSON',
        ];

        $this->assertEquals($expectedFormats, Project::WEBHOOK_FORMATS);
    }

    public function test_project_generates_magic_key_on_creation(): void
    {
        $project = Project::factory()->create(['magic_key' => null]);

        $this->assertNotNull($project->magic_key);
        $this->assertEquals(64, strlen($project->magic_key));
    }

    public function test_generate_magic_key_returns_64_character_string(): void
    {
        $key = Project::generateMagicKey();

        $this->assertEquals(64, strlen($key));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $key);
    }

    public function test_regenerate_magic_key_updates_key(): void
    {
        $project = Project::factory()->create();
        $oldKey = $project->magic_key;

        $project->regenerateMagicKey();

        $this->assertNotEquals($oldKey, $project->magic_key);
        $this->assertEquals(64, strlen($project->magic_key));
    }

    public function test_find_by_magic_key_returns_project(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $found = Project::findByMagicKey($project->magic_key);

        $this->assertNotNull($found);
        $this->assertEquals($project->id, $found->id);
    }

    public function test_find_by_magic_key_returns_null_for_inactive_project(): void
    {
        $project = Project::factory()->inactive()->create();

        $found = Project::findByMagicKey($project->magic_key);

        $this->assertNull($found);
    }

    public function test_find_by_magic_key_returns_null_for_invalid_key(): void
    {
        Project::factory()->create();

        $found = Project::findByMagicKey('invalid-key');

        $this->assertNull($found);
    }

    public function test_project_has_many_logs(): void
    {
        $project = Project::factory()->create();
        Log::factory()->count(3)->create(['project_id' => $project->id]);

        $this->assertCount(3, $project->logs);
        $this->assertInstanceOf(Log::class, $project->logs->first());
    }

    public function test_project_has_many_webhook_deliveries(): void
    {
        // Fake events to prevent LogCreated from triggering webhooks
        Event::fake(['App\Events\LogCreated']);

        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        WebhookDelivery::factory()->count(2)->create([
            'project_id' => $project->id,
            'log_id' => $log->id,
        ]);

        $this->assertCount(2, $project->webhookDeliveries);
    }

    public function test_has_infinite_retention_returns_true_for_negative_one(): void
    {
        $project = Project::factory()->infiniteRetention()->create();

        $this->assertTrue($project->hasInfiniteRetention());
    }

    public function test_has_infinite_retention_returns_false_for_positive_days(): void
    {
        $project = Project::factory()->create(['retention_days' => 30]);

        $this->assertFalse($project->hasInfiniteRetention());
    }

    public function test_has_webhook_returns_true_when_url_and_enabled(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => true,
        ]);

        $this->assertTrue($project->hasWebhook());
    }

    public function test_has_webhook_returns_false_when_disabled(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => false,
        ]);

        $this->assertFalse($project->hasWebhook());
    }

    public function test_has_webhook_returns_false_when_no_url(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => null,
            'webhook_enabled' => true,
        ]);

        $this->assertFalse($project->hasWebhook());
    }

    public function test_has_webhook_url_returns_true_when_url_set(): void
    {
        $project = Project::factory()->create([
            'webhook_url' => 'https://hooks.slack.com/test',
            'webhook_enabled' => false, // Even if disabled
        ]);

        $this->assertTrue($project->hasWebhookUrl());
    }

    public function test_regenerate_webhook_secret_creates_new_secret(): void
    {
        $project = Project::factory()->create(['webhook_secret' => null]);

        $project->regenerateWebhookSecret();

        $this->assertNotNull($project->webhook_secret);
        $this->assertEquals(64, strlen($project->webhook_secret));
    }

    public function test_magic_key_is_hidden_in_array(): void
    {
        $project = Project::factory()->create();
        $array = $project->toArray();

        $this->assertArrayNotHasKey('magic_key', $array);
    }

    public function test_webhook_secret_is_hidden_in_array(): void
    {
        $project = Project::factory()->create(['webhook_secret' => 'secret123']);
        $array = $project->toArray();

        $this->assertArrayNotHasKey('webhook_secret', $array);
    }

    public function test_project_uses_uuid(): void
    {
        $project = Project::factory()->create();

        // UUID format: 8-4-4-4-12 hexadecimal characters
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $project->id
        );
    }

    public function test_deleting_project_cascades_to_logs(): void
    {
        $project = Project::factory()->create();
        Log::factory()->count(3)->create(['project_id' => $project->id]);

        $this->assertEquals(3, Log::where('project_id', $project->id)->count());

        $project->delete();

        $this->assertEquals(0, Log::where('project_id', $project->id)->count());
    }
}
