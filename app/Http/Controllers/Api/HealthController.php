<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring and uptime checks.
     *
     * GET /api/health
     */
    public function __invoke(): JsonResponse
    {
        $healthy = true;
        $checks = [];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $healthy = false;
        }

        // Check cache
        try {
            cache()->put('health_check', true, 1);
            cache()->forget('health_check');
            $checks['cache'] = 'ok';
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
