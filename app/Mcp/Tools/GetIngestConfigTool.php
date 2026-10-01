<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesProjects;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_ingest_config')]
#[Description('Ingest endpoint, secret project key (X-Project-Key) and ready .env lines to hook an application up to this project. Needs edit permission. The key is a secret: write it into the target app\'s .env only, never into code, docs or commits.')]
class GetIngestConfigTool extends Tool
{
    use ResolvesProjects;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate(['project' => 'required|string']);

        [$project, $error] = $this->resolveProject($request, $data['project'], requireEdit: true);
        if ($error) {
            return Response::error($error);
        }

        $this->audit($request, 'get_ingest_config', $project);

        $base = rtrim(config('app.url'), '/');

        return Response::json([
            'project' => $project->name,
            'is_active' => $project->is_active,
            'endpoint' => $base,
            'ingest_url' => $base.'/api/ingest',
            'header' => 'X-Project-Key',
            'project_key' => $project->magic_key,
            'allowed_domains' => $project->allowed_domains ?: 'unrestricted',
            'env' => [
                'LOGGER_ENDPOINT' => $base,
                'LOGGER_PROJECT_KEY' => $project->magic_key,
            ],
            'payload' => 'POST JSON {level, message, context?}; batch supported (openapi.yaml in the logger repo).',
            'laravel' => 'Custom monolog channel "logger" with a handler posting to {LOGGER_ENDPOINT}/api/ingest with the X-Project-Key header; example in the logger README, section "Laravel Integration Example". Add the channel to config/logging.php and to the stack in LOG_STACK.',
        ]);
    }
}
