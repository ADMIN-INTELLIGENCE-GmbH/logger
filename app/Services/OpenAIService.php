<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

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
        return ! empty($this->apiKey);
    }

    /**
     * Analyze a log entry using OpenAI.
     * Can accept either a Log model (for backward compatibility) or an array of selected data.
     *
     * @param Log|array $logData Either a Log model or an array of log data
     */
    public function analyzeLog($logData): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'OpenAI is not configured. Please set OPENAI_API_KEY in your environment.',
            ];
        }

        // Convert Log model to array if needed
        if ($logData instanceof Log) {
            $logArray = [
                'level' => $logData->level,
                'message' => $logData->message,
                'channel' => $logData->channel,
                'controller' => $logData->controller,
                'route_name' => $logData->route_name,
                'method' => $logData->method,
                'request_url' => $logData->request_url,
                'user_id' => $logData->user_id,
                'ip_address' => $logData->ip_address,
                'user_agent' => $logData->user_agent,
                'app_env' => $logData->app_env,
                'app_debug' => $logData->app_debug,
                'referrer' => $logData->referrer,
                'context' => $logData->context,
                'extra' => $logData->extra,
            ];
        } else {
            $logArray = $logData;
        }

        $prompt = $this->buildPrompt($logArray);

        try {
            $headers = [
                'Authorization' => 'Bearer '.$this->apiKey,
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
                    'error' => 'OpenAI API error: '.$errorMessage,
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! $content) {
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
                'error' => 'Failed to connect to OpenAI: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'An error occurred: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build the prompt for log analysis with redaction.
     * Accepts either a Log model or an array of log data.
     *
     * @param Log|array $logData
     */
    protected function buildPrompt($logData): string
    {
        // Extract values from either model or array
        $level = $logData instanceof Log ? $logData->level : ($logData['level'] ?? 'unknown');
        $message = $logData instanceof Log ? $logData->message : ($logData['message'] ?? '');
        $channel = $logData instanceof Log ? $logData->channel : ($logData['channel'] ?? null);
        $controller = $logData instanceof Log ? $logData->controller : ($logData['controller'] ?? null);
        $route_name = $logData instanceof Log ? $logData->route_name : ($logData['route_name'] ?? null);
        $method = $logData instanceof Log ? $logData->method : ($logData['method'] ?? null);
        $request_url = $logData instanceof Log ? $logData->request_url : ($logData['request_url'] ?? null);
        $user_id = $logData instanceof Log ? $logData->user_id : ($logData['user_id'] ?? null);
        $ip_address = $logData instanceof Log ? $logData->ip_address : ($logData['ip_address'] ?? null);
        $user_agent = $logData instanceof Log ? $logData->user_agent : ($logData['user_agent'] ?? null);
        $app_env = $logData instanceof Log ? $logData->app_env : ($logData['app_env'] ?? null);
        $app_debug = $logData instanceof Log ? $logData->app_debug : ($logData['app_debug'] ?? null);
        $referrer = $logData instanceof Log ? $logData->referrer : ($logData['referrer'] ?? null);
        $context = $logData instanceof Log ? $logData->context : ($logData['context'] ?? null);
        $extra = $logData instanceof Log ? $logData->extra : ($logData['extra'] ?? null);

        $lines = [
            "Laravel log - give me a TL;DR:",
            "- **What**: One-line explanation",
            "- **Why**: Likely cause (1-2 sentences)",
            "- **Fix**: Quick solution (code snippet if needed)",
            "",
            'Log: '.strtoupper($level),
            'Message: '.$message,
        ];

        $fields = [
            'Channel' => $channel,
            'Controller' => $controller,
            'Route' => $route_name,
            'Method' => $method,
            'Request URL' => $request_url,
            'User ID' => $user_id,
            'IP Address' => $ip_address,
            'User Agent' => $user_agent,
            'Referrer' => $referrer,
        ];

        foreach ($fields as $label => $value) {
            if ($value) {
                $lines[] = "$label: $value";
            }
        }

        if ($app_env) {
            $lines[] = 'Environment: '.$app_env.($app_debug ? ' (debug mode)' : '');
        }

        if ($context) {
            $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            // Limit context size for concise responses
            if (strlen($contextJson) > 4000) {
                $contextJson = substr($contextJson, 0, 4000)."\n... (truncated)";
            }
            $lines[] = "Context:\n".$contextJson;
        }

        if ($extra && ! empty($extra)) {
            $extraJson = json_encode($extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (strlen($extraJson) > 1000) {
                $extraJson = substr($extraJson, 0, 1000)."\n... (truncated)";
            }
            $lines[] = "Extra (Monolog data):\n".$extraJson;
        }

        return implode("\n", $lines);
    }
}
