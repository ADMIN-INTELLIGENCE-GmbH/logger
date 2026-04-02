<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalCheck;
use App\Services\IssueQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalCheckIssuesController extends Controller
{
    public function __invoke(Request $request, ExternalCheck $externalCheck, IssueQueryService $issueQueryService): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $externalCheck->matchesToken($token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid bearer token',
            ], 401);
        }

        if (! $externalCheck->enabled) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'External check is disabled',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $issueQueryService->query($externalCheck),
        ]);
    }
}
