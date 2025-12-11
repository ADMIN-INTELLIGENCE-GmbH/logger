<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Handle incoming server stats.
     *
     * POST /api/stats
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

        $project = Project::where('magic_key', $projectKey)->first();

        if (! $project || ! $project->is_active) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid project key or project is inactive',
            ], 401);
        }

        // 2. Validate and update stats
        // We accept any JSON payload as stats, as requested ("sky is the limit")
        $stats = $request->all();

        $project->update([
            'server_stats' => $stats,
            'last_server_stats_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stats updated',
        ]);
    }
}
