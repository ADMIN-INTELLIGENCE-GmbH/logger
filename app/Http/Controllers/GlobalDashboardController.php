<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalDashboardController extends Controller
{
    /**
     * Show the global dashboard with all projects.
     */
    public function index(Request $request): View
    {
        $projects = Project::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($project) {
                $now = now();
                $last24h = $now->copy()->subDay();

                // Get stats for last 24 hours
                $totalLogs = Log::where('project_id', $project->id)
                    ->where('created_at', '>=', $last24h)
                    ->count();

                $errorLogs = Log::where('project_id', $project->id)
                    ->where('created_at', '>=', $last24h)
                    ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                    ->count();

                return [
                    'project' => $project,
                    'total_logs_24h' => $totalLogs,
                    'error_logs_24h' => $errorLogs,
                    'error_rate' => $totalLogs > 0 ? round(($errorLogs / $totalLogs) * 100, 1) : 0,
                ];
            });

        // Overall stats
        $totalProjects = Project::where('is_active', true)->count();
        $totalLogs24h = Log::where('created_at', '>=', now()->subDay())->count();
        $totalErrors24h = Log::where('created_at', '>=', now()->subDay())
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->count();

        $overallStats = [
            'total_projects' => $totalProjects,
            'total_logs_24h' => $totalLogs24h,
            'total_errors_24h' => $totalErrors24h,
            'overall_error_rate' => $totalLogs24h > 0 ? round(($totalErrors24h / $totalLogs24h) * 100, 1) : 0,
        ];

        $project = null; // Ensure project-specific nav is hidden

        return view('dashboard', compact('projects', 'overallStats', 'project'));
    }
}
