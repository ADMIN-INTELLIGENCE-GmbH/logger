<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IngestLogRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

        if (! $project) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid project key or project is inactive',
            ], 401);
        }

        // 2. Security: Check Origin against Allowed Domains
        $origin = $request->header('Origin');
        if (! $project->isOriginAllowed($origin)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Origin not allowed',
            ], 403);
        }

        // 3. Check if this is a batch request or single log
        if ($request->isBatchRequest()) {
            return $this->handleBatchIngest($request, $project);
        }

        return $this->handleSingleIngest($request, $project);
    }

    /**
     * Handle single log ingestion.
     */
    protected function handleSingleIngest(IngestLogRequest $request, Project $project): JsonResponse
    {
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

        // The LogCreated event is automatically fired via model events

        return response()->json([
            'success' => true,
            'message' => 'Log entry created',
            'log_id' => $log->id,
        ], 201);
    }

    /**
     * Handle batch log ingestion.
     */
    protected function handleBatchIngest(IngestLogRequest $request, Project $project): JsonResponse
    {
        // Support both raw array format and wrapped {"logs": [...]} format
        $logs = $request->has('logs') ? $request->input('logs', []) : $request->all();
        $createdLogs = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($logs as $index => $logData) {
                try {
                    $log = $project->logs()->create([
                        'level' => $logData['level'],
                        'channel' => $logData['channel'] ?? null,
                        'message' => $logData['message'],
                        'context' => $request->getTruncatedContext($logData),
                        'extra' => $request->getTruncatedExtra($logData),
                        'controller' => $request->getLogController($logData),
                        'route_name' => $logData['route_name'] ?? null,
                        'method' => $request->getHttpMethod($logData),
                        'request_url' => $logData['request_url'] ?? null,
                        'user_id' => $logData['user_id'] ?? null,
                        'ip_address' => $logData['ip_address'] ?? $request->ip(),
                        'user_agent' => $logData['user_agent'] ?? $request->userAgent(),
                        'app_env' => $logData['app_env'] ?? null,
                        'app_debug' => $logData['app_debug'] ?? null,
                        'referrer' => $logData['referrer'] ?? null,
                        'logged_at' => $request->getLogDatetime($logData),
                    ]);

                    $createdLogs[] = $log->id;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // The LogCreated event is automatically fired via model events for each log

        $response = [
            'success' => true,
            'message' => 'Batch log ingestion completed',
            'created' => count($createdLogs),
            'failed' => count($errors),
            'log_ids' => $createdLogs,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, 201);
    }
}
