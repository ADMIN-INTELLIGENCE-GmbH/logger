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

#[Name('resolve_log_group')]
#[Description('Mark a log group as fixed. The group is every log with the same level and first 200 message chars as log_id (the grouping of new_log_groups), up to now. Logs stay stored but are hidden from search_logs and new_log_groups unless include_resolved is set. Later occurrences are unresolved again, so a regression shows up as new. reopen=true clears the mark. Needs edit permission.')]
class ResolveLogGroupTool extends Tool
{
    use ResolvesProjects;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'log_id' => $schema->integer()->description('Any log of the group, e.g. sample_log_id from new_log_groups.')->required(),
            'note' => $schema->string()->description('What fixed it, e.g. commit or ticket. Max 500 chars.'),
            'reopen' => $schema->boolean()->description('Clear the resolved mark instead of setting it.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'project' => 'required|string',
            'log_id' => 'required|integer',
            'note' => 'nullable|string|max:500',
            'reopen' => 'nullable|boolean',
        ]);

        [$project, $error] = $this->resolveProject($request, $data['project'], requireEdit: true);
        if ($error) {
            return Response::error($error);
        }

        $sample = Log::where('project_id', $project->id)->find($data['log_id']);
        if (! $sample) {
            return Response::error('Log '.$data['log_id'].' not found in project '.$project->name.'.');
        }

        $reopen = (bool) ($data['reopen'] ?? false);
        $head = mb_substr($sample->message, 0, 200);

        $query = Log::where('project_id', $project->id)
            ->where('level', $sample->level)
            ->where(DB::raw('SUBSTR(message, 1, 200)'), $head)
            ->where('created_at', '<=', now());

        $affected = $reopen
            ? $query->whereNotNull('resolved_at')->update([
                'resolved_at' => null,
                'resolved_by' => null,
                'resolution_note' => null,
            ])
            : $query->whereNull('resolved_at')->update([
                'resolved_at' => now(),
                'resolved_by' => $request->user()->id,
                'resolution_note' => $data['note'] ?? null,
            ]);

        $this->audit($request, $reopen ? 'reopen_log_group' : 'resolve_log_group', $project);

        return Response::json([
            'project' => $project->name,
            'action' => $reopen ? 'reopened' : 'resolved',
            'level' => $sample->level,
            'message' => $head,
            'logs_affected' => $affected,
        ]);
    }
}
