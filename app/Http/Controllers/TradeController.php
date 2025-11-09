<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function index()
    {
        $trades = Trade::withCount('candidates', 'batches')->latest()->paginate(20);
        return view('admin.trades.index', compact('trades'));
    }

    public function create()
    {
        return view('admin.trades.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:trades,name',
            'code' => 'required|string|unique:trades,code',
            'category' => 'required|string',
            'duration_weeks' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        Trade::create($validated);

        return redirect()->route('trades.index')->with('success', 'Trade created successfully!');
    }

    public function show(Trade $trade)
    {
        $trade->load('candidates', 'batches');
        return view('admin.trades.show', compact('trade'));
    }

    public function edit(Trade $trade)
    {
        return view('admin.trades.edit', compact('trade'));
    }

    public function update(Request $request, Trade $trade)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:trades,name,' . $trade->id,
            'code' => 'required|string|unique:trades,code,' . $trade->id,
            'category' => 'required|string',
            'duration_weeks' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $trade->update($validated);

        return redirect()->route('trades.index')->with('success', 'Trade updated successfully!');
    }

    public function destroy(Trade $trade)
    {
        $trade->delete();
        return back()->with('success', 'Trade deleted successfully!');
    }
    public function apiList()
    {
        $trades = Trade::where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();
        
        return response()->json($trades);
    }
}