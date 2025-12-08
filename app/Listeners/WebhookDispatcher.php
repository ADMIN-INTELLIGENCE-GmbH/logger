<?php

namespace App\Listeners;

use App\Events\LogCreated;
use App\Models\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LaravelLog;

class WebhookDispatcher implements ShouldQueue
{
    /**
     * The minimum level threshold for webhook notifications.
     * Only logs at or above this level will trigger webhooks.
     */
    protected string $threshold = 'error';

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

        // Check if project has a webhook URL configured
        if (!$project->hasWebhook()) {
            return;
        }

        // Check if log level meets the threshold
        if (!$log->meetsThreshold($this->threshold)) {
            return;
        }

        // Send webhook notification
        $this->sendWebhook($project->webhook_url, $log);
    }

    /**
     * Send the webhook notification.
     */
    protected function sendWebhook(string $url, Log $log): void
    {
        $payload = $this->formatPayload($log);

        try {
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->post($url, $payload);

            if (!$response->successful()) {
                LaravelLog::warning('Webhook failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'log_id' => $log->id,
                ]);
            }
        } catch (\Exception $e) {
            LaravelLog::error('Webhook exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
            ]);
        }
    }

    /**
     * Format the webhook payload.
     * Supports Slack/Mattermost/Discord webhook format.
     */
    protected function formatPayload(Log $log): array
    {
        $project = $log->project;
        $levelEmoji = $this->getLevelEmoji($log->level);
        $levelColor = $this->getLevelColor($log->level);

        return [
            // Slack format
            'text' => "{$levelEmoji} [{$project->name}] {$log->level}: {$log->message}",
            'username' => 'Logger',
            'icon_emoji' => ':warning:',
            
            // Rich attachment format (Slack/Mattermost)
            'attachments' => [
                [
                    'color' => $levelColor,
                    'title' => "Log Entry - {$log->level}",
                    'text' => $log->message,
                    'fields' => array_filter([
                        [
                            'title' => 'Project',
                            'value' => $project->name,
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
                    ]),
                    'ts' => $log->created_at->timestamp,
                ],
            ],

            // Discord embed format
            'embeds' => [
                [
                    'title' => "{$levelEmoji} Log Entry - {$log->level}",
                    'description' => $log->message,
                    'color' => $this->getLevelColorDecimal($log->level),
                    'fields' => array_filter([
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
                        $log->user_id ? [
                            'name' => 'User ID',
                            'value' => $log->user_id,
                            'inline' => true,
                        ] : null,
                    ]),
                    'timestamp' => $log->created_at->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Get emoji for log level.
     */
    protected function getLevelEmoji(string $level): string
    {
        return match ($level) {
            'debug' => '🔍',
            'info' => 'ℹ️',
            'error' => '🔴',
            'critical' => '🚨',
            default => '📝',
        };
    }

    /**
     * Get hex color for log level.
     */
    protected function getLevelColor(string $level): string
    {
        return match ($level) {
            'debug' => '#6c757d',
            'info' => '#17a2b8',
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
            'error' => 14431557,
            'critical' => 7478308,
            default => 7105644,
        };
    }
}
