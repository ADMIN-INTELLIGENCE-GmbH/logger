<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesProjects;
use App\Mcp\Concerns\ScopedByToken;
use App\Models\Log;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('project_overview')]
#[Description('Project summary for a time window: log counts per level, top error controllers and routes, last server stats.')]
#[IsReadOnly]
class ProjectOverviewTool extends Tool
{
    use ResolvesProjects;
    use ScopedByToken;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'since' => $schema->string()->description('Relative ("24h", "7d") or a date. Default 24h.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        $top = fn (string $key) => $schema->array()->items($schema->object([
            $key => $schema->string()->required(),
            'errors' => $schema->integer()->required(),
        ])->withoutAdditionalProperties())->description('Up to 10, error level and above, most errors first.')->required();

        return [
            'id' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'is_active' => $schema->boolean()->required(),
            'retention_days' => $schema->integer()->required(),
            'since' => $schema->string()->description('ISO 8601 with offset.')->required(),
            'logs_by_level' => $schema->object()->description('Level name to count; only levels that occurred. Values are integers.')->required(),
            'top_error_controllers' => $top('controller'),
            'top_error_routes' => $top('route_name'),
            'server_stats' => $schema->union(['object', 'array', 'null'])->description('Last server stats pushed by the app, free-form (memory, cpu, disk, queue, os, host ...); null if none.')->required(),
            'server_stats_at' => $schema->string()->nullable()->description('ISO 8601 with offset.')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'project' => 'required|string',
            'since' => 'nullable|string',
        ]);

        [$project, $error] = $this->resolveProject($request, $data['project']);
        if ($error) {
            return Response::error($error);
        }

        try {
            $since = $this->parseTime($data['since'] ?? '24h');
        } catch (\Throwable) {
            return Response::error('Could not parse since. Use "24h", "7d" or a date.');
        }

        $base = Log::where('project_id', $project->id)->where('created_at', '>=', $since);
        $errors = $this->levelsFrom('error');

        $byLevel = (clone $base)->select('level', DB::raw('COUNT(*) as n'))
            ->groupBy('level')->pluck('n', 'level');

        $top = fn (string $column) => (clone $base)->whereIn('level', $errors)->whereNotNull($column)
            ->select($column, DB::raw('COUNT(*) as n'))
            ->groupBy($column)->orderByDesc('n')->limit(10)->get()
            ->map(fn ($row) => [$column => $row->{$column}, 'errors' => (int) $row->n])->all();

        return Response::structured([
            'id' => $project->id,
            'name' => $project->name,
            'is_active' => $project->is_active,
            'retention_days' => $project->retention_days,
            'since' => $since->toIso8601String(),
            'logs_by_level' => (object) $byLevel->map(fn ($n) => (int) $n)->all(), // object even when empty
            'top_error_controllers' => $top('controller'),
            'top_error_routes' => $top('route_name'),
            'server_stats' => $project->server_stats,
            'server_stats_at' => $project->last_server_stats_at?->toIso8601String(),
        ]);
    }
}
