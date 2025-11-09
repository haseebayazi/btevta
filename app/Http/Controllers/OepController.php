<?php

namespace App\Http\Controllers;

use App\Models\Oep;
use Illuminate\Http\Request;

class OepController extends Controller
{
    public function index()
    {
        $oeps = Oep::withCount('candidates', 'departures')->latest()->paginate(20);
        return view('admin.oeps.index', compact('oeps'));
    }

    public function create()
    {
        return view('admin.oeps.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:oeps,name',
            'code' => 'required|string|unique:oeps,code',
            'country' => 'required|string',
            'city' => 'nullable|string',
            'contact_person' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'address' => 'nullable|string',
            'license_number' => 'nullable|string',
        ]);

        Oep::create($validated);

        return redirect()->route('oeps.index')->with('success', 'OEP created successfully!');
    }

    public function show(Oep $oep)
    {
        $oep->load('candidates', 'departures');
        return view('admin.oeps.show', compact('oep'));
    }

    public function edit(Oep $oep)
    {
        return view('admin.oeps.edit', compact('oep'));
    }

    public function update(Request $request, Oep $oep)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:oeps,name,' . $oep->id,
            'code' => 'required|string|unique:oeps,code,' . $oep->id,
            'country' => 'required|string',
            'city' => 'nullable|string',
            'contact_person' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'address' => 'nullable|string',
            'license_number' => 'nullable|string',
        ]);

        $oep->update($validated);

        return redirect()->route('oeps.index')->with('success', 'OEP updated successfully!');
    }

    public function destroy(Oep $oep)
    {
        $oep->delete();
        return back()->with('success', 'OEP deleted successfully!');
    }
    public function apiList()
    {
        $oeps = Oep::where('is_active', true)
            ->select('id', 'name', 'code', 'country')
            ->get();
        
        return response()->json($oeps);
    }
}