<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Search for tags by name.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');

        if (empty($query)) {
            return response()->json(['tags' => []]);
        }

        $tags = Tag::where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');

        return response()->json(['tags' => $tags]);
    }
}
