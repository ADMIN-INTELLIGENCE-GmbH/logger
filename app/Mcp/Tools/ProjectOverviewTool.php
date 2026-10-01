<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesProjects;
use App\Models\Log;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
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

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'since' => $schema->string()->description('Relative ("24h", "7d") or a date. Default 24h.'),
        ];
    }

    public function handle(Request $request): Response
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

        return Response::json([
            'id' => $project->id,
            'name' => $project->name,
            'is_active' => $project->is_active,
            'retention_days' => $project->retention_days,
            'since' => $since->toIso8601String(),
            'logs_by_level' => $byLevel->map(fn ($n) => (int) $n)->all(),
            'top_error_controllers' => $top('controller'),
            'top_error_routes' => $top('route_name'),
            'server_stats' => $project->server_stats,
            'server_stats_at' => $project->last_server_stats_at?->toIso8601String(),
        ]);
    }
}
