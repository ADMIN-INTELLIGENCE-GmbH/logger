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
        // Get all error logs with controllers
        $logs = Log::where('project_id', $project->id)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->whereNotNull('controller')
            ->select('id', 'controller', 'context')
            ->get();

        // Group by controller class
        $grouped = $logs->groupBy('controller');

        // Convert to collection with totals and method breakdown
        $failingControllers = $grouped->map(function ($logs, $controller) {
            // Extract methods from context traces
            $methods = $logs->groupBy(function ($log) {
                // Context is already an array due to model casting
                $context = $log->context;
                if (is_array($context) && isset($context['trace']) && is_array($context['trace'])) {
                    foreach ($context['trace'] as $frame) {
                        if (isset($frame['function'])) {
                            return $frame['function'];
                        }
                    }
                }
                return 'unknown';
            })->map(function ($methodLogs) {
                return $methodLogs->count();
            })->sortByDesc(function ($count) {
                return $count;
            });

            return (object)[
                'controller' => $controller,
                'total' => $logs->count(),
                'methods' => $methods,
            ];
        })->sortByDesc('total')->take(50)->values();

        // Also get total errors for context
        $totalErrors = Log::where('project_id', $project->id)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
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
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Group by unique error messages
        $uniqueErrors = Log::where('project_id', $project->id)
            ->where('controller', $controller)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
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
