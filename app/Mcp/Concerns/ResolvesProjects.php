<?php

namespace App\Mcp\Concerns;

use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log as AppLog;
use Laravel\Mcp\Request;

/**
 * Shared helpers for MCP tools: project lookup scoped to the token owner,
 * relative time parsing, level filtering.
 */
trait ResolvesProjects
{
    /**
     * Find a project the token owner may view (or edit) by UUID or name.
     *
     * @return array{0: ?Project, 1: ?string} project or error message
     */
    protected function resolveProject(Request $request, string $reference, bool $requireEdit = false): array
    {
        /** @var User $user */
        $user = $request->user();

        $visible = $requireEdit
            ? Project::editableBy($user)
            : Project::visibleTo($user);

        $project = (clone $visible)->whereKey($reference)->first();

        if (! $project) {
            $matches = (clone $visible)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($reference)])
                ->get();

            if ($matches->isEmpty()) {
                $matches = (clone $visible)
                    ->where('name', 'like', '%'.addcslashes($reference, '%_\\').'%')
                    ->get();
            }

            if ($matches->count() > 1) {
                return [null, 'Ambiguous project "'.$reference.'": '.$matches->pluck('name')->implode(', ').'. Use the id.'];
            }

            $project = $matches->first();
        }

        if (! $project) {
            $hint = $requireEdit ? ' (or no edit permission)' : '';

            return [null, 'No project "'.$reference.'" found'.$hint.'. Use list_projects.'];
        }

        return [$project, null];
    }

    /**
     * Parse "30m", "24h", "7d", "2w" or any date string into a Carbon instance.
     */
    protected function parseTime(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (preg_match('/^(\d+)\s*([mhdw])$/i', trim($value), $m)) {
            $unit = ['m' => 'minutes', 'h' => 'hours', 'd' => 'days', 'w' => 'weeks'][strtolower($m[2])];

            return now()->sub($unit, (int) $m[1]);
        }

        return Carbon::parse($value);
    }

    /**
     * All levels at or above the given one.
     *
     * @return list<string>
     */
    protected function levelsFrom(string $minLevel): array
    {
        $min = Log::LEVEL_SEVERITY[$minLevel] ?? 0;

        return array_keys(array_filter(Log::LEVEL_SEVERITY, fn ($sev) => $sev >= $min));
    }

    protected function clip(?string $text, int $length = 300): ?string
    {
        if ($text === null || mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length).'… ['.(mb_strlen($text) - $length).' more chars]';
    }

    protected function audit(Request $request, string $action, Project $project): void
    {
        AppLog::info('mcp: '.$action, [
            'user_id' => $request->user()?->id,
            'project_id' => $project->id,
        ]);
    }
}
