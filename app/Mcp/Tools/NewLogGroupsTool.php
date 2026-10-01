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

#[Name('new_log_groups')]
#[Description('Distinct log messages (level + first 200 chars) that appeared for the first time since a point in time, with counts and a sample log id. Start here for "what is new". Messages with embedded ids/paths may fragment into separate groups.')]
#[IsReadOnly]
class NewLogGroupsTool extends Tool
{
    use ResolvesProjects;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name.')->required(),
            'since' => $schema->string()->description('Relative ("24h", "7d") or a date. Default 24h.'),
            'min_level' => $schema->string()->enum(Log::LEVELS)->description('Default "warning".'),
            'limit' => $schema->integer()->description('Default 30, max 100.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'project' => 'required|string',
            'since' => 'nullable|string',
            'min_level' => 'nullable|in:'.implode(',', Log::LEVELS),
            'limit' => 'nullable|integer|min:1',
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

        $levels = $this->levelsFrom($data['min_level'] ?? 'warning');
        $limit = min((int) ($data['limit'] ?? 30), 100);

        $groups = Log::where('project_id', $project->id)
            ->whereIn('level', $levels)
            ->select([
                'level',
                DB::raw('SUBSTR(message, 1, 200) as message_head'),
                DB::raw('COUNT(*) as occurrences'),
                DB::raw('MIN(created_at) as first_seen'),
                DB::raw('MAX(created_at) as last_seen'),
                DB::raw('MAX(id) as sample_log_id'),
            ])
            ->groupBy('level', DB::raw('SUBSTR(message, 1, 200)'))
            ->havingRaw('MIN(created_at) >= ?', [$since])
            ->orderByDesc('occurrences')
            ->limit($limit)
            ->get();

        return Response::json([
            'project' => $project->name,
            'new_since' => $since->toIso8601String(),
            'groups' => $groups->map(fn ($g) => [
                'level' => $g->level,
                'message' => $g->message_head,
                'occurrences' => (int) $g->occurrences,
                'first_seen' => $g->first_seen,
                'last_seen' => $g->last_seen,
                'sample_log_id' => (int) $g->sample_log_id,
            ])->all(),
        ]);
    }
}
