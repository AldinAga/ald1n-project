<?php

declare(strict_types=1);

namespace App\Http\Controllers;

// ux-maximal-phase3-global-command-search-batch2-v5

use App\Services\GlobalCommandSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GlobalCommandSearchController extends Controller
{
    public function search(Request $request, GlobalCommandSearchService $search): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'items' => [],
                'results' => [],
                'sections' => [],
            ]);
        }

        if (mb_strlen($query) > 80) {
            $query = mb_substr($query, 0, 80);
        }

        $payload = $search->search($request->user(), $query, 5);
        $payload['results'] = $payload['items'];

        return response()->json($payload);
    }
}
