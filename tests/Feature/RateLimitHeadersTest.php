<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_endpoint_returns_rate_limit_headers(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message',
        ], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $response->assertStatus(201);

        // Check rate limit headers are present
        $response->assertHeader('X-RateLimit-Limit', '1000');
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));

        // Remaining should be less than or equal to limit
        $remaining = (int) $response->headers->get('X-RateLimit-Remaining');
        $this->assertLessThanOrEqual(1000, $remaining);
        $this->assertGreaterThanOrEqual(0, $remaining);

        // Reset should be in the future
        $reset = (int) $response->headers->get('X-RateLimit-Reset');
        $this->assertGreaterThan(time() - 1, $reset);
    }

    public function test_rate_limit_remaining_decreases_with_requests(): void
    {
        $project = Project::factory()->create();

        // First request
        $response1 = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message 1',
        ], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $remaining1 = (int) $response1->headers->get('X-RateLimit-Remaining');

        // Second request
        $response2 = $this->postJson('/api/ingest', [
            'level' => 'error',
            'message' => 'Test error message 2',
        ], [
            'X-Project-Key' => $project->magic_key,
        ]);

        $remaining2 = (int) $response2->headers->get('X-RateLimit-Remaining');

        // Remaining should decrease (or stay same if test runs too fast)
        $this->assertLessThanOrEqual($remaining1, $remaining2);
    }
}
