<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ScopedByToken;
use App\Models\Log;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_projects')]
#[Description('List projects visible to you with id, name, active flag, permission and log counts (24h total and 24h error+).')]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
    use ScopedByToken;

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'projects' => $schema->array()->items($schema->object([
                'id' => $schema->string()->required(),
                'name' => $schema->string()->required(),
                'is_active' => $schema->boolean()->required(),
                'permission' => $schema->string()->enum(['edit', 'view'])->required(),
                'retention_days' => $schema->integer()->required(),
                'logs_24h' => $schema->integer()->required(),
                'errors_24h' => $schema->integer()->description('Level error and above.')->required(),
                'last_log_at' => $schema->string()->nullable()->description('Server time, "YYYY-MM-DD HH:MM:SS"; null if no logs.')->required(),
            ])->withoutAdditionalProperties())->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        $projects = Project::visibleTo($user)->orderBy('name')->get();
        $since = now()->subDay();

        $errorLevels = ['error', 'critical', 'alert', 'emergency'];

        $rows = $projects->map(function (Project $project) use ($user, $since, $errorLevels) {
            $recent = Log::where('project_id', $project->id)->where('created_at', '>=', $since);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'is_active' => $project->is_active,
                'permission' => $user->canEditProject($project) ? 'edit' : 'view',
                'retention_days' => $project->retention_days,
                'logs_24h' => (clone $recent)->count(),
                'errors_24h' => (clone $recent)->whereIn('level', $errorLevels)->count(),
                'last_log_at' => Log::where('project_id', $project->id)->max('created_at'),
            ];
        });

        return Response::structured(['projects' => $rows->values()->all()]);
    }
}
