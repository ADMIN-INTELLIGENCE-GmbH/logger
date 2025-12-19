<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_requires_project_key(): void
    {
        $response = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_ingest_rejects_invalid_project_key(): void
    {
        $response = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message',
        ], [
            'X-Project-Key' => 'invalid-key',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_ingest_creates_log_entry(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message',
            'context' => ['file' => 'test.php', 'line' => 42],
            'controller' => 'App\\Http\\Controllers\\TestController',
            'route_name' => 'test.route',
            'method' => 'POST',
            'user_id' => 'user-123',
        ], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('logs', [
            'project_id' => $project->id,
            'level' => 'error',
            'message' => 'Test error message',
            'controller' => 'App\\Http\\Controllers\\TestController',
        ]);
    }

    public function test_ingest_validates_required_fields(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson('/api/ingest', [], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['level', 'message']);
    }

    public function test_ingest_validates_log_level(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson('/api/ingest', [
            'level' => 'invalid-level',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    public function test_ingest_rejects_inactive_project(): void
    {
        $project = Project::factory()->inactive()->create();

        $response = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message',
        ], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(401);
    }

    public function test_ingest_allows_any_origin_when_no_domains_configured(): void
    {
        $project = Project::factory()->create(['allowed_domains' => []]);

        $response = $this->postJson('/api/ingest', [
            'level' => 'info',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
            'Origin' => 'https://random-site.com',
        ]);

        $response->assertStatus(201);
    }

    public function test_ingest_allows_matching_origin(): void
    {
        $project = Project::factory()->create([
            'allowed_domains' => ['example.com', 'app.test.com'],
        ]);

        $response = $this->postJson('/api/ingest', [
            'level' => 'info',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
            'Origin' => 'https://example.com',
        ]);

        $response->assertStatus(201);
    }

    public function test_ingest_rejects_non_matching_origin(): void
    {
        $project = Project::factory()->create([
            'allowed_domains' => ['example.com'],
        ]);

        $response = $this->postJson('/api/ingest', [
            'level' => 'info',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
            'Origin' => 'https://evil.com',
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Forbidden', 'message' => 'Origin not allowed']);
    }

    public function test_ingest_rejects_missing_origin_when_domains_configured(): void
    {
        $project = Project::factory()->create([
            'allowed_domains' => ['example.com'],
        ]);

        $response = $this->postJson('/api/ingest', [
            'level' => 'info',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
            // No Origin header
        ]);

        $response->assertStatus(403);
    }

    public function test_ingest_allows_wildcard_subdomain(): void
    {
        $project = Project::factory()->create([
            'allowed_domains' => ['*.example.com'],
        ]);

        $response = $this->postJson('/api/ingest', [
            'level' => 'info',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
            'Origin' => 'https://sub.example.com',
        ]);

        $response->assertStatus(201);

        // Should also match nested
        $response = $this->postJson('/api/ingest', [
            'level' => 'info',
            'message' => 'Test message',
        ], [
            'X-Project-Key' => $project->magic_key,
            'Origin' => 'https://deep.sub.example.com',
        ]);

        $response->assertStatus(201);
    }
}
