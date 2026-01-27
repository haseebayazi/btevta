<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCandidateLicenseRequest;
use App\Models\Candidate;
use App\Models\CandidateLicense;
use Illuminate\Support\Facades\Storage;

class CandidateLicenseController extends Controller
{
    /**
     * Store a new license
     */
    public function store(Candidate $candidate, StoreCandidateLicenseRequest $request)
    {
        $this->authorize('create', [CandidateLicense::class, $candidate]);

        $data = $request->validated();

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = sprintf(
                '%s_license_%s_%s.%s',
                $candidate->btevta_id,
                $data['license_type'],
                now()->format('YmdHis'),
                $file->getClientOriginalExtension()
            );

            $data['file_path'] = $file->storeAs(
                "candidate-licenses/{$candidate->id}",
                $filename,
                'private'
            );
        }

        $data['candidate_id'] = $candidate->id;
        $license = CandidateLicense::create($data);

        // Log activity
        activity()
            ->performedOn($license)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'license_type' => $data['license_type'],
                'license_name' => $data['license_name'],
            ])
            ->log('Candidate license added');

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', 'License added successfully.');
    }

    /**
     * Update a license
     */
    public function update(Candidate $candidate, CandidateLicense $license, StoreCandidateLicenseRequest $request)
    {
        $this->authorize('update', $license);

        $data = $request->validated();

        // Handle file upload if present
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($license->file_path) {
                Storage::disk('private')->delete($license->file_path);
            }

            $file = $request->file('file');
            $filename = sprintf(
                '%s_license_%s_%s.%s',
                $candidate->btevta_id,
                $data['license_type'],
                now()->format('YmdHis'),
                $file->getClientOriginalExtension()
            );

            $data['file_path'] = $file->storeAs(
                "candidate-licenses/{$candidate->id}",
                $filename,
                'private'
            );
        }

        $license->update($data);

        // Log activity
        activity()
            ->performedOn($license)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'license_type' => $data['license_type'],
            ])
            ->log('Candidate license updated');

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', 'License updated successfully.');
    }

    /**
     * Delete a license
     */
    public function destroy(Candidate $candidate, CandidateLicense $license)
    {
        $this->authorize('delete', $license);

        // Delete file if exists
        if ($license->file_path) {
            Storage::disk('private')->delete($license->file_path);
        }

        // Log activity
        activity()
            ->performedOn($license)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'license_type' => $license->license_type,
                'license_name' => $license->license_name,
            ])
            ->log('Candidate license deleted');

        $license->delete();

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', 'License deleted successfully.');
    }
}
