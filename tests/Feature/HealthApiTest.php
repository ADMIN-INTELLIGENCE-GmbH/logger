<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database',
                    'cache',
                ],
                'version',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                ],
            ]);
    }

    public function test_health_endpoint_includes_version(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $this->assertArrayHasKey('version', $response->json());
    }

    public function test_health_endpoint_includes_iso8601_timestamp(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $timestamp = $response->json('timestamp');
        $this->assertNotNull($timestamp);

        // Verify it's a valid ISO 8601 timestamp
        $parsed = \DateTime::createFromFormat(\DateTime::ATOM, $timestamp);
        $this->assertInstanceOf(\DateTime::class, $parsed);
    }
}
