<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogExplorerController extends Controller
{
    /**
     * List logs with filtering and pagination.
     */
    public function index(Request $request, Project $project): View
    {
        $query = Log::where('project_id', $project->id);

        // Apply filters
        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('controller')) {
            $query->where('controller', 'like', '%' . $request->input('controller') . '%');
        }

        if ($request->filled('route_name')) {
            $query->where('route_name', $request->input('route_name'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->input('ip_address'));
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        // Order by most recent
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = min($request->input('per_page', 25), 100);
        $logs = $query->paginate($perPage);

        return view('projects.logs.index', compact('project', 'logs'));
    }

    /**
     * Show a single log entry with full context.
     */
    public function show(Project $project, Log $log): JsonResponse
    {
        // Ensure the log belongs to the project
        if ($log->project_id !== $project->id) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Log entry not found in this project',
            ], 404);
        }

        return response()->json([
            'log' => $log,
            'context_formatted' => $log->context ? json_encode($log->context, JSON_PRETTY_PRINT) : null,
        ]);
    }
}
