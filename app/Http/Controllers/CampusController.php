<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    public function index()
    {
        $campuses = Campus::withCount('candidates', 'batches')->latest()->paginate(20);
        return view('admin.campuses.index', compact('campuses'));
    }

    public function create()
    {
        return view('admin.campuses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:campuses,name',
            'location' => 'required|string',
            'province' => 'required|string',
            'district' => 'required|string',
            'address' => 'nullable|string',
            'contact_person' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
        ]);

        Campus::create($validated);

        return redirect()->route('campuses.index')->with('success', 'Campus created successfully!');
    }

    public function show(Campus $campus)
    {
        $campus->load('candidates', 'batches');
        return view('admin.campuses.show', compact('campus'));
    }

    public function edit(Campus $campus)
    {
        return view('admin.campuses.edit', compact('campus'));
    }

    public function update(Request $request, Campus $campus)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:campuses,name,' . $campus->id,
            'location' => 'required|string',
            'province' => 'required|string',
            'district' => 'required|string',
            'address' => 'nullable|string',
            'contact_person' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
        ]);

        $campus->update($validated);

        return redirect()->route('campuses.index')->with('success', 'Campus updated successfully!');
    }

    public function destroy(Campus $campus)
    {
        $campus->delete();
        return back()->with('success', 'Campus deleted successfully!');
    }
    public function apiList()
    {
        $campuses = Campus::where('is_active', true)
            ->select('id', 'name', 'city')
            ->get();
        
        return response()->json($campuses);
    }
}