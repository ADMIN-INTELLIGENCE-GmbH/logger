<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IngestLogRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    /**
     * Handle incoming log ingestion.
     *
     * POST /api/ingest
     */
    public function __invoke(IngestLogRequest $request): JsonResponse
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

        // 2. Create new Log entry (validation handled by FormRequest)
        $log = $project->logs()->create([
            'level' => $request->input('level'),
            'channel' => $request->input('channel'),
            'message' => $request->input('message'),
            'context' => $request->getTruncatedContext(),
            'extra' => $request->getTruncatedExtra(),
            'controller' => $request->getLogController(),
            'route_name' => $request->input('route_name'),
            'method' => $request->getHttpMethod(),
            'request_url' => $request->input('request_url'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->input('ip_address', $request->ip()),
            'user_agent' => $request->input('user_agent', $request->userAgent()),
            'app_env' => $request->input('app_env'),
            'app_debug' => $request->input('app_debug'),
            'referrer' => $request->input('referrer'),
            'logged_at' => $request->getLogDatetime(),
        ]);

        // 3. The LogCreated event is automatically fired via model events

        return response()->json([
            'success' => true,
            'message' => 'Log entry created',
            'log_id' => $log->id,
        ], 201);
    }
}
