<?php

namespace App\Http\Controllers;

use App\Models\RemittanceBeneficiary;
use App\Models\Candidate;
use Illuminate\Http\Request;

class RemittanceBeneficiaryController extends Controller
{
    /**
     * Display a listing of beneficiaries for a candidate.
     */
    public function index($candidateId)
    {
        $this->authorize('viewAny', RemittanceBeneficiary::class);

        $candidate = Candidate::findOrFail($candidateId);
        $beneficiaries = RemittanceBeneficiary::where('candidate_id', $candidateId)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('remittances.beneficiaries.index', compact('candidate', 'beneficiaries'));
    }

    /**
     * Show the form for creating a new beneficiary.
     */
    public function create($candidateId)
    {
        $this->authorize('create', RemittanceBeneficiary::class);

        $candidate = Candidate::findOrFail($candidateId);

        return view('remittances.beneficiaries.create', compact('candidate'));
    }

    /**
     * Store a newly created beneficiary.
     */
    public function store(Request $request, $candidateId)
    {
        $this->authorize('create', RemittanceBeneficiary::class);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'relationship' => 'required|string',
            'cnic' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'mobile_wallet' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['candidate_id'] = $candidateId;
        $validated['is_primary'] = $request->has('is_primary');

        $beneficiary = RemittanceBeneficiary::create($validated);

        // If marked as primary, ensure others are not primary
        if ($beneficiary->is_primary) {
            $beneficiary->setPrimary();
        }

        return redirect()
            ->route('beneficiaries.index', $candidateId)
            ->with('success', 'Beneficiary added successfully.');
    }

    /**
     * Show the form for editing the specified beneficiary.
     */
    public function edit($id)
    {
        $beneficiary = RemittanceBeneficiary::findOrFail($id);
        $this->authorize('update', $beneficiary);

        $candidate = $beneficiary->candidate;

        return view('remittances.beneficiaries.edit', compact('beneficiary', 'candidate'));
    }

    /**
     * Update the specified beneficiary.
     */
    public function update(Request $request, $id)
    {
        $beneficiary = RemittanceBeneficiary::findOrFail($id);
        $this->authorize('update', $beneficiary);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'relationship' => 'required|string',
            'cnic' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'mobile_wallet' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_primary'] = $request->has('is_primary');
        $validated['is_active'] = $request->has('is_active');

        $beneficiary->update($validated);

        // If marked as primary, ensure others are not primary
        if ($beneficiary->is_primary) {
            $beneficiary->setPrimary();
        }

        return redirect()
            ->route('beneficiaries.index', $beneficiary->candidate_id)
            ->with('success', 'Beneficiary updated successfully.');
    }

    /**
     * Remove the specified beneficiary.
     */
    public function destroy($id)
    {
        $beneficiary = RemittanceBeneficiary::findOrFail($id);
        $this->authorize('delete', $beneficiary);

        $candidateId = $beneficiary->candidate_id;

        $beneficiary->delete();

        return redirect()
            ->route('beneficiaries.index', $candidateId)
            ->with('success', 'Beneficiary deleted successfully.');
    }

    /**
     * Set beneficiary as primary.
     */
    public function setPrimary($id)
    {
        $beneficiary = RemittanceBeneficiary::findOrFail($id);
        $this->authorize('setPrimary', $beneficiary);

        $beneficiary->setPrimary();

        return back()->with('success', 'Primary beneficiary updated successfully.');
    }

    /**
     * Get beneficiaries data for AJAX requests.
     * AUDIT FIX: Added missing endpoint for remittances create/edit forms
     */
    public function data($candidateId)
    {
        $this->authorize('viewAny', RemittanceBeneficiary::class);

        $beneficiaries = RemittanceBeneficiary::where('candidate_id', $candidateId)
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'relationship', 'account_number', 'iban', 'mobile_wallet', 'bank_name']);

        return response()->json($beneficiaries);
    }
}
