<?php

namespace App\Services;

use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Marks logs as fixed (or reopens them). Resolved logs stay stored; triage
 * views hide them. Shared by the log explorer and the MCP tool.
 */
class LogResolver
{
    /**
     * Length of the message prefix that defines a log group (same as new_log_groups).
     */
    public const GROUP_PREFIX = 200;

    /**
     * All logs of the sample's group up to now: same project, level and message prefix.
     */
    public function groupQuery(Log $sample): Builder
    {
        return Log::where('project_id', $sample->project_id)
            ->where('level', $sample->level)
            ->where(DB::raw('SUBSTR(message, 1, '.self::GROUP_PREFIX.')'), mb_substr($sample->message, 0, self::GROUP_PREFIX))
            ->where('created_at', '<=', now());
    }

    /**
     * @return int number of logs newly marked resolved
     */
    public function resolve(Builder $query, User $user, ?string $note = null): int
    {
        return $query->whereNull('resolved_at')->update([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_note' => $note,
        ]);
    }

    /**
     * @return int number of logs reopened
     */
    public function reopen(Builder $query): int
    {
        return $query->whereNotNull('resolved_at')->update([
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_note' => null,
        ]);
    }
}
