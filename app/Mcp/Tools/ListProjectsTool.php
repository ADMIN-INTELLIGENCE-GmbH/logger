<?php

namespace App\Mcp\Tools;

use App\Models\Log;
use App\Models\Project;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_projects')]
#[Description('List projects visible to you with id, name, active flag, permission and log counts (24h total and 24h error+).')]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
    public function handle(Request $request): Response
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

        return Response::json($rows->values()->all());
    }
}
