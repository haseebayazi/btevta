<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\Candidate;
use App\Models\EmployerDocument;
use App\ValueObjects\EmploymentPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployerService
{
    /**
     * Create employer with optional documents
     */
    public function createEmployer(array $data, array $documents = []): Employer
    {
        return DB::transaction(function () use ($data, $documents) {
            $employer = Employer::create($data);

            foreach ($documents as $doc) {
                $this->addDocument($employer, $doc['file'], $doc['type'], $doc['data'] ?? []);
            }

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer created');

            return $employer;
        });
    }

    /**
     * Update employer
     */
    public function updateEmployer(Employer $employer, array $data): Employer
    {
        $employer->update($data);

        activity()
            ->performedOn($employer)
            ->causedBy(auth()->user())
            ->log('Employer updated');

        return $employer;
    }

    /**
     * Add document to employer
     */
    public function addDocument(Employer $employer, $file, string $type, array $data = []): EmployerDocument
    {
        $path = $file->store("employers/{$employer->id}/documents", 'private');

        return EmployerDocument::create([
            'employer_id' => $employer->id,
            'document_type' => $type,
            'document_name' => $data['name'] ?? $file->getClientOriginalName(),
            'document_path' => $path,
            'document_number' => $data['number'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Delete employer document
     */
    public function deleteDocument(EmployerDocument $document): void
    {
        Storage::disk('private')->delete($document->document_path);

        activity()
            ->performedOn($document->employer)
            ->causedBy(auth()->user())
            ->withProperties(['document' => $document->document_name])
            ->log('Employer document deleted');

        $document->delete();
    }

    /**
     * Set default employment package
     */
    public function setDefaultPackage(Employer $employer, array $packageData): void
    {
        $package = new EmploymentPackage(
            baseSalary: (float) ($packageData['base_salary'] ?? 0),
            currency: $packageData['currency'] ?? 'SAR',
            housingAllowance: (float) ($packageData['housing_allowance'] ?? 0),
            foodAllowance: (float) ($packageData['food_allowance'] ?? 0),
            transportAllowance: (float) ($packageData['transport_allowance'] ?? 0),
            otherAllowance: (float) ($packageData['other_allowance'] ?? 0),
            benefits: $packageData['benefits'] ?? null,
        );

        $employer->default_package = $package->toArray();
        $employer->save();

        activity()
            ->performedOn($employer)
            ->causedBy(auth()->user())
            ->log('Default employment package updated');
    }

    /**
     * Assign candidate to employer
     */
    public function assignCandidate(
        Employer $employer,
        Candidate $candidate,
        string $employmentType = 'initial',
        ?array $customPackage = null
    ): void {
        // Check if already assigned as active
        $existing = $employer->candidates()
            ->where('candidate_id', $candidate->id)
            ->wherePivot('status', 'active')
            ->exists();

        if ($existing) {
            throw new \Exception('Candidate is already actively assigned to this employer.');
        }

        $employer->assignCandidate($candidate, $employmentType, $customPackage);
    }

    /**
     * Verify employer
     */
    public function verifyEmployer(Employer $employer): void
    {
        $employer->verify();
    }

    /**
     * Get employer candidates with optional status filter
     */
    public function getEmployerCandidates(Employer $employer, ?string $status = null)
    {
        $query = $employer->candidates()->with(['campus', 'trade']);

        if ($status) {
            $query->wherePivot('status', $status);
        }

        return $query->get();
    }

    /**
     * Get employer dashboard data
     */
    public function getDashboard(): array
    {
        $employers = Employer::with(['country', 'tradeRelation', 'documents'])
            ->withCount(['candidates as active_candidates_count' => function ($query) {
                $query->wherePivot('status', 'active');
            }])
            ->withCount(['candidates as total_candidates_count'])
            ->get();

        return [
            'summary' => [
                'total' => $employers->count(),
                'active' => $employers->where('is_active', true)->count(),
                'verified' => $employers->where('verified', true)->count(),
                'unverified' => $employers->where('verified', false)->count(),
                'with_expiring_permission' => $employers->filter(fn($e) => $e->permission_expiring)->count(),
                'with_expired_permission' => $employers->filter(fn($e) => $e->permission_expired)->count(),
            ],
            'by_country' => $employers->groupBy(fn($e) => $e->country?->name ?? 'Unknown')->map->count()->sortDesc(),
            'by_sector' => $employers->whereNotNull('sector')->groupBy('sector')->map->count()->sortDesc(),
            'top_employers' => $employers->sortByDesc('active_candidates_count')->take(10)->values(),
            'expiring_permissions' => $employers->filter(fn($e) => $e->permission_expiring)->values(),
            'expired_permissions' => $employers->filter(fn($e) => $e->permission_expired)->values(),
            'recent_employers' => $employers->sortByDesc('created_at')->take(5)->values(),
        ];
    }
}
