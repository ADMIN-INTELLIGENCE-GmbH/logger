<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Project;
use App\Services\OpenAIService;
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

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('search')) {
            // Use full-text search scope (falls back to LIKE for SQLite)
            $query->searchMessage($request->input('search'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('controller')) {
            $controller = $request->input('controller');
            // Controller field stores the full controller class name, so use exact match
            $query->where('controller', '=', $controller);
        }

        // Filter by controller method if specified (extracted from context trace)
        if ($request->filled('method')) {
            $method = $request->input('method');
            // Search the context trace for this method
            if ($method !== 'unknown') {
                // For real methods, search in the context trace (JSON has "function": "methodName")
                $query->whereRaw('context LIKE ?', ['%"function": "'.$method.'"%']);
            } else {
                // For unknown, we need logs where context has no function field
                $query->whereRaw('context NOT LIKE ?', ['%"function"%']);
            }
        }

        if ($request->filled('route_name')) {
            $query->where('route_name', $request->input('route_name'));
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->input('ip_address'));
        }

        if ($request->filled('app_env')) {
            $query->where('app_env', $request->input('app_env'));
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

    /**
     * Analyze a log entry using OpenAI.
     * The fields to include are selected by the user on the frontend,
     * but the actual data is built on the backend to prevent injection.
     */
    public function analyze(Request $request, Project $project, Log $log, OpenAIService $openAIService): JsonResponse
    {
        // Ensure the log belongs to the project
        if ($log->project_id !== $project->id) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Log entry not found in this project',
            ], 404);
        }

        // Get selected fields from request (user-selected, but server builds the data)
        $selectedFields = $request->input('fields', []);
        
        // Validate fields are booleans
        $selectedFields = array_map(fn ($value) => (bool) $value, $selectedFields);

        // Build the data to send to OpenAI based on selected fields
        $logData = [
            'level' => $log->level,
            'message' => $log->message,
        ];

        // Add optional fields only if selected
        if ($selectedFields['channel'] ?? false) {
            $logData['channel'] = $log->channel;
        }
        if ($selectedFields['controller'] ?? false) {
            $logData['controller'] = $log->controller;
        }
        if ($selectedFields['route'] ?? false) {
            $logData['route_name'] = $log->route_name;
        }
        if ($selectedFields['method'] ?? false) {
            $logData['method'] = $log->method;
        }
        if ($selectedFields['environment'] ?? false) {
            $logData['app_env'] = $log->app_env;
            $logData['app_debug'] = $log->app_debug;
        }
        if ($selectedFields['context'] ?? false) {
            $logData['context'] = $log->context;
        }
        if ($selectedFields['extra'] ?? false) {
            $logData['extra'] = $log->extra;
        }
        if ($selectedFields['request_url'] ?? false) {
            $logData['request_url'] = $log->request_url;
        }
        if ($selectedFields['user_id'] ?? false) {
            $logData['user_id'] = $log->user_id;
        }
        if ($selectedFields['ip_address'] ?? false) {
            $logData['ip_address'] = $log->ip_address;
        }
        if ($selectedFields['user_agent'] ?? false) {
            $logData['user_agent'] = $log->user_agent;
        }
        if ($selectedFields['referrer'] ?? false) {
            $logData['referrer'] = $log->referrer;
        }

        // Send the built data to OpenAI
        $result = $openAIService->analyzeLog($logData);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'analysis' => $result['analysis'],
            'model' => $result['model'] ?? null,
        ]);
    }

    /**
     * Delete a single log entry.
     */
    public function destroy(Project $project, Log $log): JsonResponse
    {
        // Ensure the log belongs to the project
        if ($log->project_id !== $project->id) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Log entry not found in this project',
            ], 404);
        }

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log entry deleted',
        ]);
    }
}
