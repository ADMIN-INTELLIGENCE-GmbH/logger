<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class OpenAIService
{
    protected ?string $apiKey;
    protected string $model;
    protected ?string $projectId;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->model = config('openai.model', 'gpt-4o-mini');
        $this->projectId = config('openai.project_id');
    }

    /**
     * Check if OpenAI is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Analyze a log entry using OpenAI.
     */
    public function analyzeLog(Log $log): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'OpenAI is not configured. Please set OPENAI_API_KEY in your environment.',
            ];
        }

        $prompt = $this->buildPrompt($log);

        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            if ($this->projectId) {
                $headers['OpenAI-Project'] = $this->projectId;
            }

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a concise Laravel debugging assistant. Give brief, actionable answers. No fluff. Use short bullet points. Max 3-4 sentences per section.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 500,
                ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API error';
                
                return [
                    'success' => false,
                    'error' => 'OpenAI API error: ' . $errorMessage,
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return [
                    'success' => false,
                    'error' => 'No response content from OpenAI.',
                ];
            }

            return [
                'success' => true,
                'analysis' => $content,
                'model' => $this->model,
                'usage' => $data['usage'] ?? null,
            ];

        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect to OpenAI: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build the prompt for log analysis.
     */
    protected function buildPrompt(Log $log): string
    {
        $prompt = "Laravel log - give me a TL;DR:\n";
        $prompt .= "- **What**: One-line explanation\n";
        $prompt .= "- **Why**: Likely cause (1-2 sentences)\n";
        $prompt .= "- **Fix**: Quick solution (code snippet if needed)\n\n";
        $prompt .= "Log: " . strtoupper($log->level) . "\n";
        $prompt .= "Message: " . $log->message . "\n";

        if ($log->channel) {
            $prompt .= "Channel: " . $log->channel . "\n";
        }

        if ($log->controller) {
            $prompt .= "Controller: " . $log->controller . "\n";
        }

        if ($log->route_name) {
            $prompt .= "Route: " . $log->route_name . "\n";
        }

        if ($log->method) {
            $prompt .= "Method: " . $log->method . "\n";
        }

        if ($log->request_url) {
            $prompt .= "Request URL: " . $log->request_url . "\n";
        }

        if ($log->user_id) {
            $prompt .= "User ID: " . $log->user_id . "\n";
        }

        if ($log->ip_address) {
            $prompt .= "IP Address: " . $log->ip_address . "\n";
        }

        if ($log->user_agent) {
            $prompt .= "User Agent: " . $log->user_agent . "\n";
        }

        if ($log->app_env) {
            $prompt .= "Environment: " . $log->app_env . ($log->app_debug ? ' (debug mode)' : '') . "\n";
        }

        if ($log->referrer) {
            $prompt .= "Referrer: " . $log->referrer . "\n";
        }

        if ($log->context) {
            $contextJson = json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            // Limit context size for concise responses
            if (strlen($contextJson) > 4000) {
                $contextJson = substr($contextJson, 0, 4000) . "\n... (truncated)";
            }
            $prompt .= "Context:\n" . $contextJson . "\n";
        }

        if ($log->extra && !empty($log->extra)) {
            $extraJson = json_encode($log->extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (strlen($extraJson) > 1000) {
                $extraJson = substr($extraJson, 0, 1000) . "\n... (truncated)";
            }
            $prompt .= "Extra (Monolog data):\n" . $extraJson . "\n";
        }

        return $prompt;
    }
}
