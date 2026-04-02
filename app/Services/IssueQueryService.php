<?php

namespace App\Services;

use App\Models\ExternalCheck;
use App\Models\Log;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IssueQueryService
{
    public function query(ExternalCheck $externalCheck): array
    {
        $projects = $externalCheck->resolveProjects();

        $issues = $this->buildLogIssues($externalCheck, $projects)
            ->concat($this->buildResourceIssues($externalCheck, $projects))
            ->sortByDesc(fn (array $issue) => [
                $this->severityRank($issue['severity']),
                $issue['count'] ?? 0,
            ])
            ->values();

        return [
            'check' => [
                'name' => $externalCheck->name,
                'slug' => $externalCheck->slug,
                'description' => $externalCheck->description,
                'enabled' => $externalCheck->enabled,
                'min_level' => $externalCheck->min_level,
                'time_window_minutes' => $externalCheck->time_window_minutes,
                'count_threshold' => $externalCheck->count_threshold,
                'group_by' => $externalCheck->group_by,
                'group_across_projects' => $externalCheck->group_across_projects,
                'selector_tags' => $externalCheck->selector_tags ?? [],
            ],
            'matched_projects_count' => $projects->count(),
            'matched_projects' => $projects
                ->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'tags' => $project->tags->pluck('name')->values()->all(),
                ])
                ->values()
                ->all(),
            'issue_count' => $issues->count(),
            'issues' => $issues->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function buildLogIssues(ExternalCheck $externalCheck, Collection $projects): Collection
    {
        if ($projects->isEmpty()) {
            return collect();
        }

        $logs = Log::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->where('created_at', '>=', now()->subMinutes($externalCheck->time_window_minutes))
            ->whereIn('level', $this->allowedLevels($externalCheck->min_level))
            ->select('id', 'project_id', 'level', 'message', 'controller', 'route_name', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        $projectNames = $projects->pluck('name', 'id');
        $groupBy = collect($externalCheck->group_by ?: ExternalCheck::DEFAULT_GROUP_BY)
            ->filter(fn ($field) => in_array($field, ExternalCheck::GROUPABLE_FIELDS, true))
            ->values();

        return $logs
            ->groupBy(fn (Log $log) => $this->fingerprintLog($log, $groupBy, $externalCheck->group_across_projects))
            ->map(function (Collection $groupedLogs, string $fingerprint) use ($externalCheck, $projectNames, $groupBy) {
                if ($groupedLogs->count() < $externalCheck->count_threshold) {
                    return null;
                }

                /** @var \App\Models\Log $sample */
                $sample = $groupedLogs->first();
                $highestSeverity = $groupedLogs
                    ->pluck('level')
                    ->sortByDesc(fn ($level) => $this->severityRank($level))
                    ->first();

                return [
                    'type' => 'log_group',
                    'fingerprint' => $fingerprint,
                    'severity' => $highestSeverity,
                    'title' => Str::limit($sample->message, 140),
                    'count' => $groupedLogs->count(),
                    'group_by' => $groupBy->all(),
                    'group_across_projects' => $externalCheck->group_across_projects,
                    'first_seen_at' => optional($groupedLogs->min('created_at'))->toIso8601String(),
                    'last_seen_at' => optional($groupedLogs->max('created_at'))->toIso8601String(),
                    'affected_projects' => $groupedLogs
                        ->pluck('project_id')
                        ->unique()
                        ->map(fn ($projectId) => [
                            'id' => $projectId,
                            'name' => $projectNames[$projectId] ?? $projectId,
                        ])
                        ->values()
                        ->all(),
                    'sample' => [
                        'id' => $sample->id,
                        'message' => $sample->message,
                        'level' => $sample->level,
                        'controller' => $sample->controller,
                        'route_name' => $sample->route_name,
                        'created_at' => optional($sample->created_at)->toIso8601String(),
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    protected function buildResourceIssues(ExternalCheck $externalCheck, Collection $projects): Collection
    {
        return $projects
            ->flatMap(function (Project $project) use ($externalCheck) {
                $stats = $project->server_stats ?? [];
                $issues = collect();
                $memoryUsage = $stats['system']['server_memory']['percent_used'] ?? null;
                $diskUsage = $stats['system']['disk_space']['percent_used'] ?? null;

                if ($externalCheck->memory_percent_gte !== null && $memoryUsage !== null && $memoryUsage >= (float) $externalCheck->memory_percent_gte) {
                    $issues->push($this->makeResourceIssue($project, 'memory', (float) $memoryUsage, (float) $externalCheck->memory_percent_gte));
                }

                if ($externalCheck->disk_percent_gte !== null && $diskUsage !== null && $diskUsage >= (float) $externalCheck->disk_percent_gte) {
                    $issues->push($this->makeResourceIssue($project, 'disk', (float) $diskUsage, (float) $externalCheck->disk_percent_gte));
                }

                return $issues;
            })
            ->values();
    }

    protected function makeResourceIssue(Project $project, string $resource, float $value, float $threshold): array
    {
        return [
            'type' => 'resource',
            'fingerprint' => sha1($project->id.':'.$resource),
            'severity' => $value >= 95 ? 'critical' : 'warning',
            'title' => ucfirst($resource).' usage threshold exceeded',
            'count' => 1,
            'resource' => $resource,
            'current_value' => round($value, 1),
            'threshold' => round($threshold, 1),
            'affected_projects' => [[
                'id' => $project->id,
                'name' => $project->name,
            ]],
            'observed_at' => optional($project->last_server_stats_at)->toIso8601String(),
        ];
    }

    protected function fingerprintLog(Log $log, Collection $groupBy, bool $groupAcrossProjects): string
    {
        $data = [];

        if (! $groupAcrossProjects) {
            $data['project_id'] = $log->project_id;
        }

        foreach ($groupBy as $field) {
            $value = $log->{$field};

            if ($field === 'message') {
                $value = Str::squish((string) $value);
            }

            $data[$field] = $value;
        }

        return sha1(json_encode($data));
    }

    protected function allowedLevels(string $minLevel): array
    {
        $minimumSeverity = Log::LEVEL_SEVERITY[$minLevel] ?? Log::LEVEL_SEVERITY['error'];

        return collect(Log::LEVELS)
            ->filter(fn ($level) => (Log::LEVEL_SEVERITY[$level] ?? -1) >= $minimumSeverity)
            ->values()
            ->all();
    }

    protected function severityRank(string $level): int
    {
        return Log::LEVEL_SEVERITY[$level] ?? 0;
    }
}
