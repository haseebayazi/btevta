<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerResource;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployerApiController extends Controller
{
    /**
     * Get paginated list of employers
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Employer::class);

        $query = Employer::with(['country', 'creator']);

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('visa_issuing_company', 'like', "%{$search}%")
                  ->orWhere('permission_number', 'like', "%{$search}%")
                  ->orWhere('sector', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 20), 100);
        $employers = $query->latest()->paginate($perPage);

        return EmployerResource::collection($employers);
    }

    /**
     * Get single employer details
     */
    public function show(Employer $employer): EmployerResource
    {
        $this->authorize('view', $employer);

        $employer->load(['country', 'creator', 'candidates']);

        return new EmployerResource($employer);
    }

    /**
     * Search employers by name or permission number
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employer::class);

        $request->validate([
            'q' => 'required|string|min:2',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        $query = Employer::where('is_active', true);

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        $employers = $query->where(function ($q) use ($request) {
            $q->where('visa_issuing_company', 'like', "%{$request->q}%")
              ->orWhere('permission_number', 'like', "%{$request->q}%");
        })
        ->limit(10)
        ->get();

        return response()->json([
            'success' => true,
            'data' => EmployerResource::collection($employers),
        ]);
    }

    /**
     * Get employers by country
     */
    public function byCountry(int $countryId): JsonResponse
    {
        $this->authorize('viewAny', Employer::class);

        $employers = Employer::where('country_id', $countryId)
            ->where('is_active', true)
            ->with('country')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EmployerResource::collection($employers),
        ]);
    }
}
