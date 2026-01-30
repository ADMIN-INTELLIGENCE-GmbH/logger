<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalDashboardController extends Controller
{
    /**
     * Show the global dashboard with all projects.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $projects = Project::where('is_active', true)
            ->visibleTo($user)
            ->with('tags')
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
                    'dashboard_url' => route('projects.dashboard', $project),
                    'total_logs_24h' => $totalLogs,
                    'error_logs_24h' => $errorLogs,
                    'error_rate' => $totalLogs > 0 ? round(($errorLogs / $totalLogs) * 100, 1) : 0,
                    'tags' => $project->tags->pluck('name')->toArray(),
                    'log_shipper_version' => $project->log_shipper_version,
                    'app_debug' => $project->app_debug,
                ];
            })->values();

        // Overall stats
        $projectIds = $projects->pluck('project.id')->filter()->values();
        $totalProjects = $projects->count();
        $totalLogs24h = $projectIds->isEmpty()
            ? 0
            : Log::where('created_at', '>=', now()->subDay())
                ->whereIn('project_id', $projectIds)
                ->count();
        $totalErrors24h = $projectIds->isEmpty()
            ? 0
            : Log::where('created_at', '>=', now()->subDay())
                ->whereIn('project_id', $projectIds)
                ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                ->count();

        $overallStats = [
            'total_projects' => $totalProjects,
            'total_logs_24h' => $totalLogs24h,
            'total_errors_24h' => $totalErrors24h,
            'overall_error_rate' => $totalLogs24h > 0 ? round(($totalErrors24h / $totalLogs24h) * 100, 1) : 0,
        ];

        $project = null; // Ensure project-specific nav is hidden

        // Get user's hidden metrics (defaults to empty array)
        $hiddenMetrics = $user->dashboard_preferences['hidden_metrics'] ?? [];

        return view('dashboard', compact('projects', 'overallStats', 'project', 'hiddenMetrics'));
    }

    /**
     * Update user's dashboard preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'hidden_metrics' => 'required|array',
            'hidden_metrics.*' => 'string',
        ]);

        $user = $request->user();
        $user->dashboard_preferences = [
            'hidden_metrics' => $request->hidden_metrics,
        ];
        $user->save();

        return response()->json(['success' => true]);
    }
}
