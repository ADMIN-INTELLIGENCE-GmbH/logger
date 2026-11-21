<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesProjects;
use App\Mcp\Concerns\ScopedByToken;
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
    use ScopedByToken;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'log_id' => $schema->integer()->description('Log id from search_logs.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        $levels = $schema->string()->enum(Log::LEVELS)->required();
        $text = fn () => $schema->string()->nullable()->required();

        return [
            'id' => $schema->integer()->required(),
            'project_id' => $schema->string()->required(),
            'level' => $levels,
            'channel' => $text(),
            'message' => $schema->string()->required(),
            'context' => $schema->union(['object', 'array', 'null'])->description('Decoded JSON, usually with exception.class/message/file/line/trace and a trace frame list.')->required(),
            'extra' => $schema->union(['object', 'array', 'null'])->description('Decoded JSON.')->required(),
            'controller' => $text(),
            'route_name' => $text(),
            'method' => $text()->description('HTTP method.'),
            'request_url' => $text(),
            'user_id' => $text(),
            'ip_address' => $text(),
            'user_agent' => $text(),
            'app_env' => $text(),
            'app_debug' => $schema->boolean()->nullable()->required(),
            'referrer' => $text(),
            'created_at' => $schema->string()->nullable()->description('Ingest time, ISO 8601 UTC with microseconds.')->required(),
            'logged_at' => $schema->string()->nullable()->description('Time reported by the app, ISO 8601 UTC with microseconds.')->required(),
            'resolved_at' => $schema->string()->nullable()->description('ISO 8601 UTC; null = not resolved.')->required(),
            'resolved_by' => $schema->integer()->nullable()->description('User id.')->required(),
            'resolution_note' => $text(),
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
