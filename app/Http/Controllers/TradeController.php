<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    /**
     * Display a listing of trades.
     */
    public function index()
    {
        $this->authorize('viewAny', Trade::class);

        $trades = Trade::withCount(['candidates', 'batches'])
            ->latest()
            ->paginate(20);

        return view('admin.trades.index', compact('trades'));
    }

    /**
     * Show the form for creating a new trade.
     */
    public function create()
    {
        $this->authorize('create', Trade::class);

        return view('admin.trades.create');
    }

    /**
     * Store a newly created trade.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Trade::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:trades,name',
            'code' => 'required|string|max:50|unique:trades,code',
            'category' => 'nullable|string|max:100',
            'duration_weeks' => 'nullable|integer|min:1',
            'duration_months' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $validated['is_active'] = true;
            $trade = Trade::create($validated);

            // Log activity
            activity()
                ->performedOn($trade)
                ->causedBy(auth()->user())
                ->log('Trade created');

            return redirect()->route('trades.index')
                ->with('success', 'Trade created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create trade: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified trade.
     */
    public function show(Trade $trade)
    {
        $this->authorize('view', $trade);

        $trade->load(['candidates' => function ($query) {
            $query->latest()->limit(10);
        }, 'batches' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.trades.show', compact('trade'));
    }

    /**
     * Show the form for editing the trade.
     */
    public function edit(Trade $trade)
    {
        $this->authorize('update', $trade);

        return view('admin.trades.edit', compact('trade'));
    }

    /**
     * Update the specified trade.
     */
    public function update(Request $request, Trade $trade)
    {
        $this->authorize('update', $trade);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:trades,name,' . $trade->id,
            'code' => 'required|string|max:50|unique:trades,code,' . $trade->id,
            'category' => 'nullable|string|max:100',
            'duration_weeks' => 'nullable|integer|min:1',
            'duration_months' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $trade->update($validated);

            // Log activity
            activity()
                ->performedOn($trade)
                ->causedBy(auth()->user())
                ->log('Trade updated');

            return redirect()->route('trades.index')
                ->with('success', 'Trade updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update trade: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified trade.
     */
    public function destroy(Trade $trade)
    {
        $this->authorize('delete', $trade);

        try {
            // Check for associated candidates
            $candidatesCount = $trade->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete trade: {$candidatesCount} candidate(s) are associated with this trade. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated batches
            $batchesCount = $trade->batches()->count();
            if ($batchesCount > 0) {
                return back()->with('error',
                    "Cannot delete trade: {$batchesCount} batch(es) are associated with this trade. " .
                    "Please reassign or remove them first."
                );
            }

            $tradeName = $trade->name;
            $trade->delete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log("Trade deleted: {$tradeName}");

            return back()->with('success', 'Trade deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete trade: ' . $e->getMessage());
        }
    }

    /**
     * Toggle trade active status.
     */
    public function toggleStatus(Trade $trade)
    {
        $this->authorize('toggleStatus', $trade);

        try {
            $trade->is_active = !$trade->is_active;
            $trade->save();

            $status = $trade->is_active ? 'activated' : 'deactivated';

            // Log activity
            activity()
                ->performedOn($trade)
                ->causedBy(auth()->user())
                ->log("Trade {$status}");

            return back()->with('success', "Trade {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle trade status: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to get list of active trades.
     */
    public function apiList()
    {
        try {
            $trades = Trade::where('is_active', true)
                ->select('id', 'name', 'code', 'category', 'duration_months')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $trades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trades'
            ], 500);
        }
    }
}
