<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::with(['campus', 'trade'])->withCount('candidates')->latest()->paginate(20);
        return view('admin.batches.index', compact('batches'));
    }

    public function create()
    {
        $campuses = Campus::pluck('name', 'id');
        $trades = Trade::pluck('name', 'id');
        $users = User::where('role', 'trainer')->pluck('name', 'id');
        return view('admin.batches.create', compact('campuses', 'trades', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_code' => 'required|string|unique:batches,batch_code',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'required|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'trainer_id' => 'nullable|exists:users,id',
            'trainer_name' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,active,completed,cancelled',
        ]);

        Batch::create($validated);

        return redirect()->route('batches.index')->with('success', 'Batch created successfully!');
    }

    public function show(Batch $batch)
    {
        $batch->load('campus', 'trade', 'trainer', 'candidates');
        return view('admin.batches.show', compact('batch'));
    }

    public function edit(Batch $batch)
    {
        $campuses = Campus::pluck('name', 'id');
        $trades = Trade::pluck('name', 'id');
        $users = User::where('role', 'trainer')->pluck('name', 'id');
        return view('admin.batches.edit', compact('batch', 'campuses', 'trades', 'users'));
    }

    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'batch_code' => 'required|string|unique:batches,batch_code,' . $batch->id,
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'required|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'trainer_id' => 'nullable|exists:users,id',
            'trainer_name' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,active,completed,cancelled',
        ]);

        $batch->update($validated);

        return redirect()->route('batches.index')->with('success', 'Batch updated successfully!');
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();
        return back()->with('success', 'Batch deleted successfully!');
    }

    public function changeStatus(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,active,completed,cancelled',
        ]);

        $batch->update(['status' => $validated['status']]);

        return back()->with('success', 'Batch status updated successfully!');
    }

    public function apiList()
    {
        $batches = Batch::where('status', 'active')
            ->select('id', 'batch_code', 'name', 'campus_id')
            ->get();
        
        return response()->json($batches);
    }
}