<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the project dashboard.
     */
    public function index(Request $request, Project $project): View
    {
        $now = now();

        // Get time range from request (default: 24h)
        $range = $request->input('range', '24h');
        $levelFilter = $request->input('level', 'all');

        // Calculate time period based on range
        $startDate = match ($range) {
            '1h' => $now->copy()->subHour(),
            '6h' => $now->copy()->subHours(6),
            '24h' => $now->copy()->subDay(),
            '7d' => $now->copy()->subDays(7),
            '30d' => $now->copy()->subDays(30),
            '90d' => $now->copy()->subDays(90),
            default => $now->copy()->subDay(),
        };

        // Base query
        $baseQuery = Log::where('project_id', $project->id)
            ->where('created_at', '>=', $startDate);

        // Total logs in selected period
        $totalLogs = (clone $baseQuery)->count();

        // Error count in selected period (PSR-3: error and above)
        $errorLogs = (clone $baseQuery)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->count();

        // Error rate percentage
        $errorRate = $totalLogs > 0
            ? round(($errorLogs / $totalLogs) * 100, 2)
            : 0;

        // Determine grouping based on range
        $driver = DB::getDriverName();

        if (in_array($range, ['1h', '6h', '24h'])) {
            // Group by hour
            $hourExpression = match ($driver) {
                'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
                'pgsql' => "TO_CHAR(created_at, 'YYYY-MM-DD HH24:00:00')",
                default => "strftime('%Y-%m-%d %H:00:00', created_at)",
            };
            $groupFormat = 'hour';
        } else {
            // Group by day
            $hourExpression = match ($driver) {
                'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
                'pgsql' => "TO_CHAR(created_at, 'YYYY-MM-DD')",
                default => "strftime('%Y-%m-%d', created_at)",
            };
            $groupFormat = 'day';
        }

        // Build chart query with optional level filter
        $chartQuery = Log::where('project_id', $project->id)
            ->where('created_at', '>=', $startDate);

        if ($levelFilter !== 'all') {
            if ($levelFilter === 'errors') {
                // PSR-3: error (4) and above
                $chartQuery->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
            } else {
                $chartQuery->where('level', $levelFilter);
            }
        }

        $chartData = $chartQuery
            ->selectRaw("{$hourExpression} as period, level, COUNT(*) as count")
            ->groupBy('period', 'level')
            ->orderBy('period')
            ->get()
            ->groupBy('period')
            ->map(function ($logs, $period) {
                $data = [
                    'period' => $period,
                    'debug' => 0,
                    'info' => 0,
                    'notice' => 0,
                    'warning' => 0,
                    'error' => 0,
                    'critical' => 0,
                    'alert' => 0,
                    'emergency' => 0,
                ];
                foreach ($logs as $log) {
                    $data[$log->level] = $log->count;
                }

                return $data;
            })
            ->values();

        $stats = [
            'total_logs' => $totalLogs,
            'error_logs' => $errorLogs,
            'error_rate' => $errorRate,
            'range' => $range,
            'level_filter' => $levelFilter,
            'group_format' => $groupFormat,
        ];

        return view('projects.dashboard', compact('project', 'stats', 'chartData'));
    }

    /**
     * Get the magic key for a project (requires authentication).
     */
    public function showMagicKey(Project $project): JsonResponse
    {
        return response()->json([
            'magic_key' => $project->magic_key,
        ]);
    }
}
