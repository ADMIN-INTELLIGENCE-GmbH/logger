<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IngestController extends Controller
{
    /**
     * Handle incoming log ingestion.
     *
     * POST /api/ingest
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Auth: Check X-Project-Key header against projects table
        $projectKey = $request->header('X-Project-Key');

        if (empty($projectKey)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing X-Project-Key header',
            ], 401);
        }

        $project = Project::findByMagicKey($projectKey);

        if (!$project) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid project key or project is inactive',
            ], 401);
        }

        // 2. Validation: Ensure payload has required fields
        $validator = Validator::make($request->all(), [
            'level' => 'required|string|in:' . implode(',', Log::LEVELS),
            'message' => 'required|string|max:65535',
            'channel' => 'nullable|string|max:255',
            'context' => 'nullable|array',
            'extra' => 'nullable|array',
            'controller' => 'nullable|string|max:255',
            'controller_action' => 'nullable|string|max:255', // alias for controller
            'route_name' => 'nullable|string|max:255',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'request_method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS', // alias for method
            'request_url' => 'nullable|string|max:65535', // TEXT column
            'user_id' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:45', // IPv6 max length
            'user_agent' => 'nullable|string|max:1024', // can be long
            'app_env' => 'nullable|string|max:50',
            'app_debug' => 'nullable|boolean',
            'referrer' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 3. Truncate large JSON fields to prevent storage issues (max ~1MB each)
        $context = $request->input('context');
        $extra = $request->input('extra');
        
        if ($context && strlen(json_encode($context)) > 1048576) {
            $context = ['_truncated' => true, '_message' => 'Context too large, truncated'];
        }
        
        if ($extra && strlen(json_encode($extra)) > 1048576) {
            $extra = ['_truncated' => true, '_message' => 'Extra data too large, truncated'];
        }

        // 4. Storage: Create new Log entry
        $log = $project->logs()->create([
            'level' => $request->input('level'),
            'channel' => $request->input('channel'),
            'message' => $request->input('message'),
            'context' => $context,
            'extra' => $extra,
            'controller' => $request->input('controller') ?? $request->input('controller_action'),
            'route_name' => $request->input('route_name'),
            'method' => $request->input('method') ?? $request->input('request_method'),
            'request_url' => $request->input('request_url'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->input('ip_address', $request->ip()),
            'user_agent' => $request->input('user_agent', $request->userAgent()),
            'app_env' => $request->input('app_env'),
            'app_debug' => $request->input('app_debug'),
            'referrer' => $request->input('referrer'),
        ]);

        // 5. The LogCreated event is automatically fired via model events

        return response()->json([
            'success' => true,
            'message' => 'Log entry created',
            'log_id' => $log->id,
        ], 201);
    }
}
