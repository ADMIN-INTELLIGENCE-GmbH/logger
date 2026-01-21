<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        // Group by controller class (strip method if present)
        $grouped = $logs->groupBy(function ($log) {
            return Str::before($log->controller, '@');
        });

        // Convert to collection with totals and method breakdown
        $failingControllers = $grouped->map(function ($logs, $controllerClass) {
            // Group by method
            $methods = $logs->groupBy(function ($log) use ($controllerClass) {
                // If controller field has @method, use it
                if (Str::contains($log->controller, '@')) {
                    return Str::afterLast($log->controller, '@');
                }

                // Fallback: Extract from trace
                $context = $log->context;
                if (is_array($context) && isset($context['trace']) && is_array($context['trace'])) {
                    // Try to find the specific method for this controller in the stack trace
                    foreach ($context['trace'] as $frame) {
                        if (isset($frame['class']) && $frame['class'] === $controllerClass && isset($frame['function'])) {
                            return $frame['function'];
                        }
                    }

                    // Fallback: If no strict class match, try checking if the controller ends with the class name
                    foreach ($context['trace'] as $frame) {
                        if (isset($frame['class']) && str_ends_with($controllerClass, $frame['class']) && isset($frame['function'])) {
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

            return (object) [
                'controller' => $controllerClass,
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
