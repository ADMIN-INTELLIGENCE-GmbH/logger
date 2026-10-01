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

#[Name('get_log')]
#[Description('Full log entry: message, context (stack trace), extra, request data.')]
#[IsReadOnly]
class GetLogTool extends Tool
{
    use ResolvesProjects;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'log_id' => $schema->integer()->description('Log id from search_logs.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'project' => 'required|string',
            'log_id' => 'required|integer',
        ]);

        [$project, $error] = $this->resolveProject($request, $data['project']);
        if ($error) {
            return Response::error($error);
        }

        $log = Log::where('project_id', $project->id)->find($data['log_id']);
        if (! $log) {
            return Response::error('Log '.$data['log_id'].' not found in project '.$project->name.'.');
        }

        return Response::json($log->toArray());
    }
}
