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
    public function index(Project $project): View
    {
        $now = now();
        $yesterday = $now->copy()->subDay();

        // Total logs in last 24 hours
        $totalLogs24h = Log::where('project_id', $project->id)
            ->where('created_at', '>=', $yesterday)
            ->count();

        // Error count in last 24 hours
        $errorLogs24h = Log::where('project_id', $project->id)
            ->where('created_at', '>=', $yesterday)
            ->whereIn('level', ['error', 'critical'])
            ->count();

        // Error rate percentage
        $errorRate = $totalLogs24h > 0 
            ? round(($errorLogs24h / $totalLogs24h) * 100, 2) 
            : 0;

        // Logs by hour for the last 24 hours (for chart)
        // Use database-agnostic date formatting
        $driver = DB::getDriverName();
        $hourExpression = match ($driver) {
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM-DD HH24:00:00')",
            default => "strftime('%Y-%m-%d %H:00:00', created_at)", // SQLite
        };

        $chartData = Log::where('project_id', $project->id)
            ->where('created_at', '>=', $yesterday)
            ->selectRaw("{$hourExpression} as hour, level, COUNT(*) as count")
            ->groupBy('hour', 'level')
            ->orderBy('hour')
            ->get()
            ->groupBy('hour')
            ->map(function ($logs, $hour) {
                $data = ['hour' => $hour, 'info' => 0, 'debug' => 0, 'error' => 0, 'critical' => 0];
                foreach ($logs as $log) {
                    $data[$log->level] = $log->count;
                }
                return $data;
            })
            ->values();

        $stats = [
            'total_logs_24h' => $totalLogs24h,
            'error_logs_24h' => $errorLogs24h,
            'error_rate' => $errorRate,
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
