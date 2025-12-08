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
            'context' => 'nullable|array',
            'controller' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:255',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'user_id' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:45', // IPv6 max length
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 3. Storage: Create new Log entry
        $log = $project->logs()->create([
            'level' => $request->input('level'),
            'message' => $request->input('message'),
            'context' => $request->input('context'),
            'controller' => $request->input('controller'),
            'route_name' => $request->input('route_name'),
            'method' => $request->input('method'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->input('ip_address', $request->ip()),
        ]);

        // 4. The LogCreated event is automatically fired via model events

        return response()->json([
            'success' => true,
            'message' => 'Log entry created',
            'log_id' => $log->id,
        ], 201);
    }
}
