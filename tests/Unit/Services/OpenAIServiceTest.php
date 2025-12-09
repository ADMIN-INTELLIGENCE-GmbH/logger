<?php

namespace Tests\Unit\Services;

use App\Models\Log;
use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset config for each test
        Config::set('openai.api_key', null);
        Config::set('openai.model', 'gpt-4o-mini');
        Config::set('openai.project_id', null);
    }

    public function test_is_configured_returns_false_when_no_api_key(): void
    {
        Config::set('openai.api_key', null);

        $service = new OpenAIService;

        $this->assertFalse($service->isConfigured());
    }

    public function test_is_configured_returns_true_when_api_key_set(): void
    {
        Config::set('openai.api_key', 'sk-test-key');

        $service = new OpenAIService;

        $this->assertTrue($service->isConfigured());
    }

    public function test_analyze_log_returns_error_when_not_configured(): void
    {
        Config::set('openai.api_key', null);

        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        $service = new OpenAIService;
        $result = $service->analyzeLog($log);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_analyze_log_makes_correct_api_call(): void
    {
        Config::set('openai.api_key', 'sk-test-key');
        Config::set('openai.model', 'gpt-4o-mini');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is a test analysis response.']],
                ],
                'usage' => ['total_tokens' => 100],
            ], 200),
        ]);

        $project = Project::factory()->create();
        $log = Log::factory()->create([
            'project_id' => $project->id,
            'level' => 'error',
            'message' => 'Test error message',
            'context' => ['key' => 'value'],
        ]);

        $service = new OpenAIService;
        $result = $service->analyzeLog($log);

        $this->assertTrue($result['success']);
        $this->assertEquals('This is a test analysis response.', $result['analysis']);
        $this->assertEquals('gpt-4o-mini', $result['model']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-test-key')
                && $request['model'] === 'gpt-4o-mini';
        });
    }

    public function test_analyze_log_includes_project_id_header_when_configured(): void
    {
        Config::set('openai.api_key', 'sk-test-key');
        Config::set('openai.project_id', 'proj-12345');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Analysis']],
                ],
            ], 200),
        ]);

        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        $service = new OpenAIService;
        $service->analyzeLog($log);

        Http::assertSent(function ($request) {
            return $request->hasHeader('OpenAI-Project', 'proj-12345');
        });
    }

    public function test_analyze_log_handles_api_error(): void
    {
        Config::set('openai.api_key', 'sk-test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),
        ]);

        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        $service = new OpenAIService;
        $result = $service->analyzeLog($log);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Rate limit exceeded', $result['error']);
    }

    public function test_analyze_log_handles_empty_response(): void
    {
        Config::set('openai.api_key', 'sk-test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => null]],
                ],
            ], 200),
        ]);

        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        $service = new OpenAIService;
        $result = $service->analyzeLog($log);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No response content', $result['error']);
    }

    public function test_analyze_log_handles_connection_error(): void
    {
        Config::set('openai.api_key', 'sk-test-key');

        Http::fake([
            'api.openai.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $project = Project::factory()->create();
        $log = Log::factory()->create(['project_id' => $project->id]);

        $service = new OpenAIService;
        $result = $service->analyzeLog($log);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('error occurred', $result['error']);
    }
}
