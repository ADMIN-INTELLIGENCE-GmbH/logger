<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('create_project')]
#[Description('Create a project and grant the token owner edit permission. Returns id only; fetch the key with get_ingest_config. Admin only.')]
class CreateProjectTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Project name.')->required(),
            'retention_days' => $schema->integer()->enum([7, 14, 30, 90, -1])->description('Default 30, -1 = keep forever.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->can('create', Project::class)) {
            return Response::error('Only admins can create projects.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'retention_days' => 'nullable|integer|in:7,14,30,90,-1',
        ]);

        if (Project::whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists()) {
            return Response::error('A project named "'.$data['name'].'" already exists.');
        }

        $project = Project::create([
            'name' => $data['name'],
            'retention_days' => $data['retention_days'] ?? 30,
            'is_active' => true,
        ]);

        $project->users()->syncWithoutDetaching([
            $user->id => ['permission' => Project::PERMISSION_EDIT],
        ]);

        return Response::json([
            'id' => $project->id,
            'name' => $project->name,
            'retention_days' => $project->retention_days,
            'next' => 'Call get_ingest_config to fetch endpoint and key.',
        ]);
    }
}
