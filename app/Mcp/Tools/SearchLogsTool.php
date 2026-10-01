<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesProjects;
use App\Models\Log;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search_logs')]
#[Description('Search a project\'s logs, newest first. Messages are clipped; use get_log for full context and stack trace.')]
#[IsReadOnly]
class SearchLogsTool extends Tool
{
    use ResolvesProjects;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'min_level' => $schema->string()->enum(Log::LEVELS)->description('Only this level and above, e.g. "error".'),
            'level' => $schema->string()->enum(Log::LEVELS)->description('Exactly this level.'),
            'since' => $schema->string()->description('Relative ("30m", "24h", "7d", "2w") or a date. Default 24h.'),
            'until' => $schema->string()->description('Relative or a date.'),
            'search' => $schema->string()->description('Text in the message.'),
            'controller' => $schema->string()->description('Exact controller class.'),
            'route_name' => $schema->string()->description('Exact route name.'),
            'app_env' => $schema->string()->description('e.g. production.'),
            'limit' => $schema->integer()->description('Default 25, max 100.'),
            'offset' => $schema->integer()->description('For paging.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'project' => 'required|string',
            'min_level' => 'nullable|in:'.implode(',', Log::LEVELS),
            'level' => 'nullable|in:'.implode(',', Log::LEVELS),
            'since' => 'nullable|string',
            'until' => 'nullable|string',
            'search' => 'nullable|string|max:255',
            'controller' => 'nullable|string',
            'route_name' => 'nullable|string',
            'app_env' => 'nullable|string',
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:0',
        ]);

        [$project, $error] = $this->resolveProject($request, $data['project']);
        if ($error) {
            return Response::error($error);
        }

        try {
            $since = $this->parseTime($data['since'] ?? '24h');
            $until = $this->parseTime($data['until'] ?? null);
        } catch (\Throwable) {
            return Response::error('Could not parse since/until. Use "24h", "7d" or a date.');
        }

        $query = Log::where('project_id', $project->id)->where('created_at', '>=', $since);

        if ($until) {
            $query->where('created_at', '<=', $until);
        }
        if (! empty($data['min_level'])) {
            $query->whereIn('level', $this->levelsFrom($data['min_level']));
        }
        if (! empty($data['level'])) {
            $query->where('level', $data['level']);
        }
        if (! empty($data['search'])) {
            $query->searchMessage($data['search']);
        }
        foreach (['controller', 'route_name', 'app_env'] as $column) {
            if (! empty($data[$column])) {
                $query->where($column, $data[$column]);
            }
        }

        $total = (clone $query)->count();
        $limit = min((int) ($data['limit'] ?? 25), 100);
        $offset = (int) ($data['offset'] ?? 0);

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')
            ->skip($offset)->take($limit)->get();

        return Response::json([
            'project' => $project->name,
            'since' => $since->toIso8601String(),
            'total' => $total,
            'offset' => $offset,
            'returned' => $logs->count(),
            'logs' => $logs->map(fn (Log $log) => [
                'id' => $log->id,
                'at' => $log->created_at?->toIso8601String(),
                'level' => $log->level,
                'message' => $this->clip($log->message),
                'controller' => $log->controller,
                'route_name' => $log->route_name,
                'request_url' => $log->request_url,
                'app_env' => $log->app_env,
            ])->all(),
        ]);
    }
}
