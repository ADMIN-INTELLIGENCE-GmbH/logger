<?php

namespace App\Listeners;

use App\Events\LogCreated;
use App\Models\Log;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LaravelLog;

class WebhookDispatcher implements ShouldQueue
{
    /**
     * Maximum webhook deliveries per project per minute.
     */
    protected int $rateLimit = 30;

    /**
     * Rate limit window in seconds.
     */
    protected int $rateLimitWindow = 60;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LogCreated $event): void
    {
        $log = $event->log;
        $project = $log->project;

        // Check if project has a webhook URL configured and enabled
        if (! $project->hasWebhook()) {
            return;
        }

        // Check if log level meets the project's threshold
        if (! $log->meetsThreshold($project->webhook_threshold)) {
            return;
        }

        // Check rate limit
        if ($this->isRateLimited($project)) {
            LaravelLog::warning('Webhook rate limited', [
                'project_id' => $project->id,
                'log_id' => $log->id,
            ]);

            return;
        }

        // Send webhook notification
        $this->sendWebhook($project, $log);
    }

    /**
     * Check if the project is rate limited.
     */
    protected function isRateLimited(Project $project): bool
    {
        $cacheKey = "webhook_rate_limit:{$project->id}";
        $count = Cache::get($cacheKey, 0);

        if ($count >= $this->rateLimit) {
            return true;
        }

        Cache::put($cacheKey, $count + 1, $this->rateLimitWindow);

        return false;
    }

    /**
     * Send the webhook notification.
     */
    protected function sendWebhook(Project $project, Log $log, string $eventType = 'log'): void
    {
        $payload = $this->formatPayload($log, $project->webhook_format ?? 'slack');
        $url = $project->webhook_url;

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'project_id' => $project->id,
            'log_id' => $log->id,
            'url' => $url,
            'event_type' => $eventType,
            'payload' => $payload,
            'attempt' => 1,
        ]);

        try {
            $headers = $this->buildHeaders($project, $payload);

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->retry(3, 100, function ($exception, $request) use ($delivery) {
                    $delivery->increment('attempt');

                    return true;
                })
                ->post($url, $payload);

            $delivery->update([
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 1000),
                'success' => $response->successful(),
                'delivered_at' => now(),
            ]);

            if (! $response->successful()) {
                LaravelLog::warning('Webhook failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'log_id' => $log->id,
                    'delivery_id' => $delivery->id,
                ]);
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // RequestException has a response we can extract the status code from
            $delivery->update([
                'status_code' => $e->response?->status(),
                'response_body' => $e->response ? substr($e->response->body(), 0, 1000) : null,
                'error_message' => $e->getMessage(),
                'success' => false,
                'delivered_at' => now(),
            ]);

            LaravelLog::error('Webhook exception', [
                'url' => $url,
                'status' => $e->response?->status(),
                'error' => $e->getMessage(),
                'log_id' => $log->id,
                'delivery_id' => $delivery->id,
            ]);
        } catch (\Exception $e) {
            $delivery->update([
                'error_message' => $e->getMessage(),
                'success' => false,
                'delivered_at' => now(),
            ]);

            LaravelLog::error('Webhook exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
                'delivery_id' => $delivery->id,
            ]);
        }
    }

    /**
     * Send a test webhook.
     */
    public static function sendTestWebhook(Project $project): WebhookDelivery
    {
        $instance = new static;
        $payload = $instance->formatTestPayload($project, $project->webhook_format ?? 'slack');
        $url = $project->webhook_url;

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'project_id' => $project->id,
            'log_id' => null,
            'url' => $url,
            'event_type' => 'test',
            'payload' => $payload,
            'attempt' => 1,
        ]);

        try {
            $headers = $instance->buildHeaders($project, $payload);

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($url, $payload);

            $delivery->update([
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 1000),
                'success' => $response->successful(),
                'delivered_at' => now(),
            ]);
        } catch (\Exception $e) {
            $delivery->update([
                'error_message' => $e->getMessage(),
                'success' => false,
                'delivered_at' => now(),
            ]);
        }

        return $delivery->fresh();
    }

    /**
     * Format test webhook payload based on format.
     */
    protected function formatTestPayload(Project $project, string $format): array
    {
        return match ($format) {
            'slack' => $this->formatTestSlackPayload($project),
            'mattermost' => $this->formatTestMattermostPayload($project),
            'discord' => $this->formatTestDiscordPayload($project),
            'teams' => $this->formatTestTeamsPayload($project),
            'generic' => $this->formatTestGenericPayload($project),
            default => $this->formatTestSlackPayload($project),
        };
    }

    /**
     * Format test payload for Slack.
     */
    protected function formatTestSlackPayload(Project $project): array
    {
        $projectUrl = $this->getProjectUrl($project);

        return [
            'text' => "Test webhook from Logger - {$project->name}",
            'username' => 'Logger',
            'attachments' => [
                [
                    'color' => '#17a2b8',
                    'title' => 'Test Webhook',
                    'text' => 'This is a test message to verify your webhook configuration is working correctly.',
                    'fields' => [
                        ['title' => 'Project', 'value' => "<{$projectUrl}|{$project->name}>", 'short' => true],
                        ['title' => 'Timestamp', 'value' => now()->toIso8601String(), 'short' => true],
                    ],
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Format test payload for Mattermost.
     */
    protected function formatTestMattermostPayload(Project $project): array
    {
        $projectUrl = $this->getProjectUrl($project);

        return [
            'text' => "**Test webhook from Logger** - {$project->name}",
            'username' => 'Logger',
            'attachments' => [
                [
                    'color' => '#17a2b8',
                    'title' => 'Test Webhook',
                    'text' => 'This is a test message to verify your webhook configuration is working correctly.',
                    'fields' => [
                        ['title' => 'Project', 'value' => "[{$project->name}]({$projectUrl})", 'short' => true],
                        ['title' => 'Timestamp', 'value' => now()->toIso8601String(), 'short' => true],
                    ],
                ],
            ],
        ];
    }

    /**
     * Format test payload for Discord.
     */
    protected function formatTestDiscordPayload(Project $project): array
    {
        $projectUrl = $this->getProjectUrl($project);

        return [
            'username' => 'Logger',
            'embeds' => [
                [
                    'title' => 'Test Webhook',
                    'description' => 'This is a test message to verify your webhook configuration is working correctly.',
                    'url' => $projectUrl,
                    'color' => 1549464,
                    'fields' => [
                        ['name' => 'Project', 'value' => $project->name, 'inline' => true],
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'footer' => ['text' => 'Logger'],
                ],
            ],
        ];
    }

    /**
     * Format test payload for Microsoft Teams.
     */
    protected function formatTestTeamsPayload(Project $project): array
    {
        $projectUrl = $this->getProjectUrl($project);

        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => '17a2b8',
            'summary' => "Test webhook from Logger - {$project->name}",
            'sections' => [
                [
                    'activityTitle' => 'Test Webhook',
                    'activitySubtitle' => $project->name,
                    'facts' => [
                        ['name' => 'Message', 'value' => 'This is a test message to verify your webhook configuration is working correctly.'],
                        ['name' => 'Project', 'value' => $project->name],
                        ['name' => 'Timestamp', 'value' => now()->toIso8601String()],
                    ],
                    'markdown' => true,
                ],
            ],
            'potentialAction' => [
                [
                    '@type' => 'OpenUri',
                    'name' => 'View Project',
                    'targets' => [
                        ['os' => 'default', 'uri' => $projectUrl],
                    ],
                ],
            ],
        ];
    }

    /**
     * Format test payload as generic JSON.
     */
    protected function formatTestGenericPayload(Project $project): array
    {
        $projectUrl = $this->getProjectUrl($project);

        return [
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'url' => $projectUrl,
            ],
            'message' => 'This is a test message to verify your webhook configuration is working correctly.',
        ];
    }

    /**
     * Build headers for webhook request including signature.
     */
    protected function buildHeaders(Project $project, array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Logger-Webhook/1.0',
            'X-Logger-Project' => $project->id,
            'X-Logger-Timestamp' => (string) now()->timestamp,
        ];

        // Add HMAC signature if secret is configured
        if ($project->webhook_secret) {
            $payloadJson = json_encode($payload);
            $timestamp = $headers['X-Logger-Timestamp'];
            $signaturePayload = "{$timestamp}.{$payloadJson}";
            $signature = hash_hmac('sha256', $signaturePayload, $project->webhook_secret);
            $headers['X-Logger-Signature'] = "sha256={$signature}";
        }

        return $headers;
    }

    /**
     * Format the webhook payload based on the configured format.
     */
    protected function formatPayload(Log $log, string $format = 'slack'): array
    {
        return match ($format) {
            'slack' => $this->formatSlackPayload($log),
            'mattermost' => $this->formatMattermostPayload($log),
            'discord' => $this->formatDiscordPayload($log),
            'teams' => $this->formatTeamsPayload($log),
            'generic' => $this->formatGenericPayload($log),
            default => $this->formatSlackPayload($log),
        };
    }

    /**
     * Format payload for Slack.
     */
    protected function formatSlackPayload(Log $log): array
    {
        $project = $log->project;
        $levelColor = $this->getLevelColor($log->level);
        $projectUrl = $this->getProjectUrl($project);

        return [
            'text' => "[{$project->name}] {$log->level}: {$log->message}",
            'username' => 'Logger',
            'attachments' => [
                [
                    'color' => $levelColor,
                    'title' => "Log Entry - {$log->level}",
                    'text' => $log->message,
                    'fields' => array_values(array_filter([
                        [
                            'title' => 'Project',
                            'value' => "<{$projectUrl}|{$project->name}>",
                            'short' => true,
                        ],
                        [
                            'title' => 'Level',
                            'value' => strtoupper($log->level),
                            'short' => true,
                        ],
                        $log->controller ? [
                            'title' => 'Controller',
                            'value' => $log->controller,
                            'short' => true,
                        ] : null,
                        $log->route_name ? [
                            'title' => 'Route',
                            'value' => $log->route_name,
                            'short' => true,
                        ] : null,
                        $log->user_id ? [
                            'title' => 'User ID',
                            'value' => $log->user_id,
                            'short' => true,
                        ] : null,
                        $log->ip_address ? [
                            'title' => 'IP Address',
                            'value' => $log->ip_address,
                            'short' => true,
                        ] : null,
                    ])),
                    'ts' => $log->created_at->timestamp,
                ],
            ],
        ];
    }

    /**
     * Format payload for Mattermost.
     * Mattermost is mostly Slack-compatible but with some differences.
     */
    protected function formatMattermostPayload(Log $log): array
    {
        $project = $log->project;
        $levelColor = $this->getLevelColor($log->level);
        $projectUrl = $this->getProjectUrl($project);

        return [
            'text' => "**[{$project->name}]** {$log->level}: {$log->message}",
            'username' => 'Logger',
            'attachments' => [
                [
                    'color' => $levelColor,
                    'title' => "Log Entry - {$log->level}",
                    'text' => $log->message,
                    'fields' => array_values(array_filter([
                        [
                            'title' => 'Project',
                            'value' => "[{$project->name}]({$projectUrl})",
                            'short' => true,
                        ],
                        [
                            'title' => 'Level',
                            'value' => strtoupper($log->level),
                            'short' => true,
                        ],
                        $log->controller ? [
                            'title' => 'Controller',
                            'value' => $log->controller,
                            'short' => true,
                        ] : null,
                        $log->user_id ? [
                            'title' => 'User ID',
                            'value' => $log->user_id,
                            'short' => true,
                        ] : null,
                    ])),
                ],
            ],
        ];
    }

    /**
     * Format payload for Discord.
     */
    protected function formatDiscordPayload(Log $log): array
    {
        $project = $log->project;
        $projectUrl = $this->getProjectUrl($project);

        return [
            'username' => 'Logger',
            'avatar_url' => null,
            'content' => null,
            'embeds' => [
                [
                    'title' => "Log Entry - {$log->level}",
                    'description' => $log->message,
                    'url' => $projectUrl,
                    'color' => $this->getLevelColorDecimal($log->level),
                    'fields' => array_values(array_filter([
                        [
                            'name' => 'Project',
                            'value' => $project->name,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Level',
                            'value' => strtoupper($log->level),
                            'inline' => true,
                        ],
                        $log->controller ? [
                            'name' => 'Controller',
                            'value' => $log->controller,
                            'inline' => true,
                        ] : null,
                        $log->route_name ? [
                            'name' => 'Route',
                            'value' => $log->route_name,
                            'inline' => true,
                        ] : null,
                        $log->user_id ? [
                            'name' => 'User ID',
                            'value' => (string) $log->user_id,
                            'inline' => true,
                        ] : null,
                        $log->ip_address ? [
                            'name' => 'IP Address',
                            'value' => $log->ip_address,
                            'inline' => true,
                        ] : null,
                    ])),
                    'timestamp' => $log->created_at->toIso8601String(),
                    'footer' => [
                        'text' => 'Logger',
                    ],
                ],
            ],
        ];
    }

    /**
     * Format payload for Microsoft Teams.
     * Uses Adaptive Cards format.
     */
    protected function formatTeamsPayload(Log $log): array
    {
        $project = $log->project;
        $levelColor = $this->getLevelColor($log->level);
        $projectUrl = $this->getProjectUrl($project);

        // Teams uses Adaptive Cards via the webhook connector
        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => ltrim($levelColor, '#'),
            'summary' => "[{$project->name}] {$log->level}: {$log->message}",
            'sections' => [
                [
                    'activityTitle' => 'Log Entry - '.strtoupper($log->level),
                    'activitySubtitle' => $project->name,
                    'facts' => array_values(array_filter([
                        [
                            'name' => 'Message',
                            'value' => $log->message,
                        ],
                        [
                            'name' => 'Level',
                            'value' => strtoupper($log->level),
                        ],
                        $log->controller ? [
                            'name' => 'Controller',
                            'value' => $log->controller,
                        ] : null,
                        $log->route_name ? [
                            'name' => 'Route',
                            'value' => $log->route_name,
                        ] : null,
                        $log->user_id ? [
                            'name' => 'User ID',
                            'value' => (string) $log->user_id,
                        ] : null,
                        $log->ip_address ? [
                            'name' => 'IP Address',
                            'value' => $log->ip_address,
                        ] : null,
                        [
                            'name' => 'Timestamp',
                            'value' => $log->created_at->toIso8601String(),
                        ],
                    ])),
                    'markdown' => true,
                ],
            ],
            'potentialAction' => [
                [
                    '@type' => 'OpenUri',
                    'name' => 'View Project',
                    'targets' => [
                        ['os' => 'default', 'uri' => $projectUrl],
                    ],
                ],
            ],
        ];
    }

    /**
     * Format payload as generic JSON.
     * This is a clean, structured format for custom integrations.
     */
    protected function formatGenericPayload(Log $log): array
    {
        $project = $log->project;
        $projectUrl = $this->getProjectUrl($project);

        return [
            'event' => 'log.created',
            'timestamp' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'url' => $projectUrl,
            ],
            'log' => [
                'id' => $log->id,
                'level' => $log->level,
                'message' => $log->message,
                'channel' => $log->channel,
                'context' => $log->context,
                'extra' => $log->extra,
                'controller' => $log->controller,
                'route_name' => $log->route_name,
                'method' => $log->method,
                'request_url' => $log->request_url,
                'user_id' => $log->user_id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toIso8601String(),
            ],
        ];
    }

    /**
     * Get the project URL for the webhook.
     */
    protected function getProjectUrl(Project $project): string
    {
        return route('projects.logs.index', ['project' => $project->id]);
    }

    /**
     * Get hex color for log level.
     */
    protected function getLevelColor(string $level): string
    {
        return match ($level) {
            'debug' => '#6c757d',
            'info' => '#17a2b8',
            'warning' => '#ffc107',
            'error' => '#dc3545',
            'critical' => '#721c24',
            default => '#6c757d',
        };
    }

    /**
     * Get decimal color for Discord embeds.
     */
    protected function getLevelColorDecimal(string $level): int
    {
        return match ($level) {
            'debug' => 7105644,
            'info' => 1549464,
            'warning' => 16760071,
            'error' => 14431557,
            'critical' => 7478308,
            default => 7105644,
        };
    }
}
