<?php

namespace App\Services;

use App\Models\Log;
use App\Models\Project;
use App\Models\User;

class DailyDigestService
{
    /**
     * Gather digest data for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Collection|null  $projects  Optional pre-fetched projects
     * @param  \Illuminate\Support\Collection|null  $logStats  Optional pre-fetched log stats
     */
    public function gatherData(User $user, $projects = null, $logStats = null): array
    {
        // Fetch projects if not provided
        if (! $projects) {
            $projects = Project::where('is_active', true)
                ->visibleTo($user)
                ->get();
        }

        // Fetch log stats if not provided and needed
        $settings = $user->daily_digest_settings ?? [];
        if (! $logStats && ! empty($settings['logs'])) {
            $logStats = Log::where('created_at', '>=', now()->subDay())
                ->whereIn('project_id', $projects->pluck('id'))
                ->selectRaw('project_id, level, count(*) as count')
                ->groupBy('project_id', 'level')
                ->get()
                ->groupBy('project_id');
        }

        $data = [
            'logs_summary' => [],
            'memory_alerts' => [],
            'storage_alerts' => [],
        ];

        // 1. Logs Stats
        if (! empty($settings['logs'])) {
            foreach ($projects as $project) {
                // Lookup stats in memory (handle both pre-fetched collection and direct query result if we did logical check above)
                // Note: checking isset() on collection grouped by ID.
                if ($logStats && isset($logStats[$project->id])) {
                    $projectLogs = $logStats[$project->id]->pluck('count', 'level')->toArray();
                    $data['logs_summary'][] = [
                        'id' => $project->id,
                        'name' => $project->name,
                        'counts' => $projectLogs,
                    ];
                }
            }
        }

        // 2. High Memory Usage
        if (! empty($settings['memory_usage'])) {
            foreach ($projects as $project) {
                $stats = $project->server_stats ?? [];
                $memUsage = $stats['system']['server_memory']['percent_used'] ?? 0;

                if ($memUsage > 80) {
                    $data['memory_alerts'][] = [
                        'project' => $project->name,
                        'project_id' => $project->id,
                        'usage' => round($memUsage, 1),
                    ];
                }
            }
        }

        // 3. Filesize (Storage)
        if (! empty($settings['filesize'])) {
            foreach ($projects as $project) {
                $stats = $project->server_stats ?? [];
                $diskUsage = $stats['system']['disk_space']['percent_used'] ?? 0;

                if ($diskUsage > 80) {
                    $data['storage_alerts'][] = [
                        'project' => $project->name,
                        'project_id' => $project->id,
                        'usage' => round($diskUsage, 1),
                    ];
                }
            }
        }

        return $data;
    }
}
