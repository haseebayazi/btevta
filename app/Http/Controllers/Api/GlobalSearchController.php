<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GlobalSearchController extends Controller
{
    protected $searchService;

    public function __construct(GlobalSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Global search across all modules
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $this->authorize('globalSearch', User::class);

        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'types' => 'nullable|array',
            'types.*' => 'string|in:candidates,remittances,alerts,batches,trades,campuses,oeps,departures,visas',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $term = $request->input('q');
        $types = $request->input('types', []);
        $limit = $request->input('limit', 50);

        try {
            $results = $this->searchService->search($term, $types, $limit);
            $totalCount = $this->searchService->getResultCount($results);

            return response()->json([
                'success' => true,
                'query' => $term,
                'total_results' => $totalCount,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
