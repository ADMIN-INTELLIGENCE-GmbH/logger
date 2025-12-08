<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FailingControllersController extends Controller
{
    /**
     * Get aggregated error counts by controller.
     */
    public function index(Project $project): View
    {
        $failingControllers = Log::where('project_id', $project->id)
            ->whereIn('level', ['error', 'critical'])
            ->whereNotNull('controller')
            ->select('controller', DB::raw('COUNT(*) as total'))
            ->groupBy('controller')
            ->orderByDesc('total')
            ->limit(50)
            ->get();

        // Also get total errors for context
        $totalErrors = Log::where('project_id', $project->id)
            ->whereIn('level', ['error', 'critical'])
            ->count();

        return view('projects.failing-controllers.index', compact('project', 'failingControllers', 'totalErrors'));
    }

    /**
     * Get error details for a specific controller.
     */
    public function show(Project $project, string $controller): JsonResponse
    {
        $logs = Log::where('project_id', $project->id)
            ->where('controller', $controller)
            ->whereIn('level', ['error', 'critical'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Group by unique error messages
        $uniqueErrors = Log::where('project_id', $project->id)
            ->where('controller', $controller)
            ->whereIn('level', ['error', 'critical'])
            ->select('message', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as last_occurred'))
            ->groupBy('message')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return response()->json([
            'controller' => $controller,
            'recent_logs' => $logs,
            'unique_errors' => $uniqueErrors,
        ]);
    }
}
