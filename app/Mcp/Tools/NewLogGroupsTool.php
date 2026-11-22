<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesProjects;
use App\Mcp\Concerns\ScopedByToken;
use App\Models\Log;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('new_log_groups')]
#[Description('Log messages that appeared for the first time since a point in time, grouped by fingerprint (level + message with numbers, long hex ids and [session_*] masked, first 200 chars), with counts, Europe/Berlin times and up to 3 sample log ids. Start here for "what is new"; omit project to cover all projects you may see. Example: new_log_groups(since="24h", min_level="error").')]
#[IsReadOnly]
class NewLogGroupsTool extends Tool
{
    use ResolvesProjects;
    use ScopedByToken;

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project id or name. Omit for all projects you may see.'),
            'since' => $schema->string()->description('Relative ("24h", "7d") or a date. Default 24h.'),
            'min_level' => $schema->string()->enum(Log::LEVELS)->description('Default "warning".'),
            'limit' => $schema->integer()->description('Default 30, max 100.'),
            'include_resolved' => $schema->boolean()->description('Also count logs marked fixed via resolve_log_group. Default false: a fixed group that recurs shows up as new.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project name, or "all" when no project was given.')->required(),
            'new_since' => $schema->string()->description('ISO 8601 with offset.')->required(),
            'groups' => $schema->array()->items($schema->object([
                'project' => $schema->string()->required(),
                'level' => $schema->string()->enum(Log::LEVELS)->required(),
                'fingerprint' => $schema->string()->description('Masked message, first 200 chars.')->required(),
                'message' => $schema->string()->description('Raw first 200 chars of the newest sample.')->required(),
                'occurrences' => $schema->integer()->required(),
                'first_seen' => $schema->string()->description('Europe/Berlin, ISO 8601 with offset.')->required(),
                'last_seen' => $schema->string()->description('Europe/Berlin, ISO 8601 with offset.')->required(),
                'sample_log_id' => $schema->integer()->description('Newest sample; pass to get_log.')->required(),
                'samples' => $schema->array()->items($schema->object([
                    'log_id' => $schema->integer()->required(),
                    'head' => $schema->string()->description('Raw first 200 chars.')->required(),
                ])->withoutAdditionalProperties())->description('Up to 3 differing raw messages, newest first.')->required(),
            ])->withoutAdditionalProperties())->required(),
        ];
    }

    /** Masks ids so "order 123 failed" and "order 456 failed" form one group. */
    public static function fingerprint(string $head): string
    {
        $head = preg_replace('/\[session_[^\]]*\]/', '[session]', $head);
        $head = preg_replace('/\b(?=[0-9a-f]*[a-f])[0-9a-f]{8,}\b/i', '<hex>', $head);

        return mb_substr(preg_replace('/\d+/', '#', $head), 0, 200);
    }

    public function handle(Request $request): Response
    {
        $unknown = array_diff(array_keys($request->all()), ['project', 'since', 'min_level', 'limit', 'include_resolved']);
        if ($unknown) {
            return Response::error('unknown argument '.implode(', ', $unknown).'; allowed: project, since, min_level, limit, include_resolved');
        }

        $data = $request->validate([
            'project' => 'nullable|string',
            'since' => 'nullable|string',
            'min_level' => 'nullable|in:'.implode(',', Log::LEVELS),
            'limit' => 'nullable|integer|min:1',
            'include_resolved' => 'nullable|boolean',
        ]);

        if (! empty($data['project'])) {
            [$project, $error] = $this->resolveProject($request, $data['project']);
            if ($error) {
                return Response::error($error);
            }
            $projects = collect([$project]);
        } else {
            $projects = Project::visibleTo($request->user())->get();
        }

        try {
            $since = $this->parseTime($data['since'] ?? '24h');
        } catch (\Throwable) {
            return Response::error('Could not parse since. Use "24h", "7d" or a date.');
        }

        $levels = $this->levelsFrom($data['min_level'] ?? 'warning');
        $limit = min((int) ($data['limit'] ?? 30), 100);
        $names = $projects->pluck('name', 'id');

        // Pre-aggregate on the raw head in SQL (portable: SQLite in tests, MariaDB in prod),
        // then merge by fingerprint in PHP; REGEXP_REPLACE is not available on SQLite.
        $rows = Log::whereIn('project_id', $projects->pluck('id'))
            ->whereIn('level', $levels)
            ->when(empty($data['include_resolved']), fn ($q) => $q->whereNull('resolved_at'))
            ->select([
                'project_id',
                'level',
                DB::raw('SUBSTR(message, 1, 200) as message_head'),
                DB::raw('COUNT(*) as occurrences'),
                DB::raw('MIN(created_at) as first_seen'),
                DB::raw('MAX(created_at) as last_seen'),
                DB::raw('MAX(id) as sample_log_id'),
            ])
            ->groupBy('project_id', 'level', DB::raw('SUBSTR(message, 1, 200)'))
            ->get();

        $berlin = fn (string $t) => Carbon::parse($t, 'UTC')->setTimezone('Europe/Berlin');

        $groups = $rows->groupBy(fn ($r) => $r->project_id.'|'.$r->level.'|'.self::fingerprint($r->message_head))
            ->map(function ($g) use ($names) {
                $g = $g->sortByDesc('sample_log_id')->values();

                return [
                    'project' => $names[$g[0]->project_id],
                    'level' => $g[0]->level,
                    'fingerprint' => self::fingerprint($g[0]->message_head),
                    'message' => $g[0]->message_head,
                    'occurrences' => (int) $g->sum('occurrences'),
                    'first_seen' => $g->min('first_seen'),
                    'last_seen' => $g->max('last_seen'),
                    'sample_log_id' => (int) $g[0]->sample_log_id,
                    'samples' => $g->take(3)->map(fn ($r) => ['log_id' => (int) $r->sample_log_id, 'head' => $r->message_head])->all(),
                ];
            })
            ->filter(fn ($g) => $g['first_seen'] >= $since->format('Y-m-d H:i:s'))
            ->sortByDesc('occurrences')
            ->take($limit)
            ->map(fn ($g) => [...$g, 'first_seen' => $berlin($g['first_seen'])->toIso8601String(), 'last_seen' => $berlin($g['last_seen'])->toIso8601String()])
            ->values()->all();

        return Response::json([
            'project' => isset($project) ? $project->name : 'all',
            'new_since' => $since->copy()->setTimezone('Europe/Berlin')->toIso8601String(),
            'groups' => $groups,
        ]);
    }
}
