<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Candidate;
use App\Models\Country;
use App\Models\Trade;
use App\Models\EmployerDocument;
use App\Services\EmployerService;
use App\Http\Requests\StoreEmployerRequest;
use App\Http\Requests\UpdateEmployerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployerController extends Controller
{
    public function __construct(
        protected EmployerService $employerService
    ) {}

    /**
     * Display a listing of employers.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Employer::class);

        $query = Employer::with(['country', 'creator', 'tradeRelation'])
            ->withCount(['candidates']);

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by verified status
        if ($request->filled('verified')) {
            $query->where('verified', $request->boolean('verified'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('visa_issuing_company', 'like', "%{$search}%")
                  ->orWhere('permission_number', 'like', "%{$search}%")
                  ->orWhere('sector', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $employers = $query->latest()->paginate(20);
        $countries = Country::destinations()->active()->get();

        return view('admin.employers.index', compact('employers', 'countries'));
    }

    /**
     * Show the form for creating a new employer.
     */
    public function create()
    {
        $this->authorize('create', Employer::class);

        $countries = Country::destinations()->active()->get();
        $trades = Trade::active()->orderBy('name')->get();

        return view('admin.employers.create', compact('countries', 'trades'));
    }

    /**
     * Store a newly created employer.
     */
    public function store(StoreEmployerRequest $request)
    {
        try {
            $validated = $request->validated();

            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                $path = $request->file('evidence')->store('employers/evidence', 'private');
                $validated['evidence_path'] = $path;
            }

            // Handle permission document upload
            if ($request->hasFile('permission_document')) {
                $path = $request->file('permission_document')->store('employers/permissions', 'private');
                $validated['permission_document_path'] = $path;
            }

            $validated['food_by_company'] = $request->boolean('food_by_company');
            $validated['transport_by_company'] = $request->boolean('transport_by_company');
            $validated['accommodation_by_company'] = $request->boolean('accommodation_by_company');
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['created_by'] = auth()->id();

            // Build default package from package_* fields
            $validated['default_package'] = $this->buildPackageFromRequest($request);

            // Remove package_ prefixed fields before creating
            $validated = $this->removePackageFields($validated);

            $employer = Employer::create($validated);

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer created');

            return redirect()->route('admin.employers.show', $employer)
                ->with('success', 'Employer created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create employer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified employer.
     */
    public function show(Employer $employer)
    {
        $this->authorize('view', $employer);

        $employer->load(['country', 'creator', 'tradeRelation', 'verifiedByUser', 'documents' => function ($q) {
            $q->latest();
        }, 'candidates' => function ($query) {
            $query->with(['campus', 'trade'])->latest()->limit(20);
        }]);

        return view('admin.employers.show', compact('employer'));
    }

    /**
     * Show the form for editing the employer.
     */
    public function edit(Employer $employer)
    {
        $this->authorize('update', $employer);

        $countries = Country::destinations()->active()->get();
        $trades = Trade::active()->orderBy('name')->get();

        return view('admin.employers.edit', compact('employer', 'countries', 'trades'));
    }

    /**
     * Update the specified employer.
     */
    public function update(UpdateEmployerRequest $request, Employer $employer)
    {
        try {
            $validated = $request->validated();

            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                if ($employer->evidence_path) {
                    Storage::disk('private')->delete($employer->evidence_path);
                }
                $path = $request->file('evidence')->store('employers/evidence', 'private');
                $validated['evidence_path'] = $path;
            }

            // Handle permission document upload
            if ($request->hasFile('permission_document')) {
                if ($employer->permission_document_path) {
                    Storage::disk('private')->delete($employer->permission_document_path);
                }
                $path = $request->file('permission_document')->store('employers/permissions', 'private');
                $validated['permission_document_path'] = $path;
            }

            $validated['food_by_company'] = $request->boolean('food_by_company');
            $validated['transport_by_company'] = $request->boolean('transport_by_company');
            $validated['accommodation_by_company'] = $request->boolean('accommodation_by_company');
            $validated['is_active'] = $request->boolean('is_active', $employer->is_active);

            // Build default package
            $validated['default_package'] = $this->buildPackageFromRequest($request);

            // Remove package_ prefixed fields
            $validated = $this->removePackageFields($validated);

            $employer->update($validated);

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer updated');

            return redirect()->route('admin.employers.show', $employer)
                ->with('success', 'Employer updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update employer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified employer.
     */
    public function destroy(Employer $employer)
    {
        $this->authorize('delete', $employer);

        try {
            $candidatesCount = $employer->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete employer: {$candidatesCount} candidate(s) are associated. " .
                    "Please reassign or remove them first."
                );
            }

            if ($employer->evidence_path) {
                Storage::disk('private')->delete($employer->evidence_path);
            }
            if ($employer->permission_document_path) {
                Storage::disk('private')->delete($employer->permission_document_path);
            }

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer deleted');

            $employer->delete();

            return redirect()->route('admin.employers.index')
                ->with('success', 'Employer deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete employer: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of an employer.
     */
    public function toggleStatus(Employer $employer)
    {
        $this->authorize('update', $employer);

        try {
            $employer->update(['is_active' => !$employer->is_active]);

            $status = $employer->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log("Employer {$status}");

            return back()->with('success', "Employer {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update employer status: ' . $e->getMessage());
        }
    }

    /**
     * Download employer evidence file.
     */
    public function downloadEvidence(Employer $employer)
    {
        $this->authorize('view', $employer);

        if (!$employer->evidence_path || !Storage::disk('private')->exists($employer->evidence_path)) {
            return back()->with('error', 'Evidence file not found.');
        }

        return Storage::disk('private')->download(
            $employer->evidence_path,
            'employer-' . $employer->id . '-evidence.' . pathinfo($employer->evidence_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Employer dashboard with statistics.
     */
    public function dashboard()
    {
        $this->authorize('viewAny', Employer::class);

        $dashboard = $this->employerService->getDashboard();

        return view('admin.employers.dashboard', compact('dashboard'));
    }

    /**
     * Verify an employer.
     */
    public function verify(Employer $employer)
    {
        $this->authorize('verify', $employer);

        try {
            $this->employerService->verifyEmployer($employer);

            return back()->with('success', 'Employer verified successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to verify employer: ' . $e->getMessage());
        }
    }

    /**
     * Set the default employment package.
     */
    public function setPackage(Request $request, Employer $employer)
    {
        $this->authorize('update', $employer);

        $validated = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'housing_allowance' => 'nullable|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'other_allowance' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->employerService->setDefaultPackage($employer, $validated);

            return back()->with('success', 'Employment package updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update package: ' . $e->getMessage());
        }
    }

    /**
     * Upload a document for the employer.
     */
    public function uploadDocument(Request $request, Employer $employer)
    {
        $this->authorize('manageDocuments', $employer);

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'document_type' => 'required|in:license,registration,permission,contract_template,other',
            'document_name' => 'nullable|string|max:200',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'document_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->employerService->addDocument(
                $employer,
                $request->file('document'),
                $validated['document_type'],
                [
                    'name' => $validated['document_name'] ?? null,
                    'number' => $validated['document_number'] ?? null,
                    'issue_date' => $validated['issue_date'] ?? null,
                    'expiry_date' => $validated['expiry_date'] ?? null,
                    'notes' => $validated['document_notes'] ?? null,
                ]
            );

            return back()->with('success', 'Document uploaded successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Delete an employer document.
     */
    public function deleteDocument(EmployerDocument $document)
    {
        $this->authorize('manageDocuments', $document->employer);

        try {
            $this->employerService->deleteDocument($document);

            return back()->with('success', 'Document deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Assign a candidate to the employer.
     */
    public function assignCandidate(Request $request, Employer $employer)
    {
        $this->authorize('assignCandidate', $employer);

        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'employment_type' => 'required|in:initial,transfer,switch',
            'custom_base_salary' => 'nullable|numeric|min:0',
            'custom_currency' => 'nullable|string|size:3',
            'custom_housing_allowance' => 'nullable|numeric|min:0',
            'custom_food_allowance' => 'nullable|numeric|min:0',
            'custom_transport_allowance' => 'nullable|numeric|min:0',
            'custom_other_allowance' => 'nullable|numeric|min:0',
        ]);

        try {
            $candidate = Candidate::findOrFail($validated['candidate_id']);

            $customPackage = null;
            if ($request->filled('custom_base_salary')) {
                $customPackage = [
                    'base_salary' => $validated['custom_base_salary'] ?? 0,
                    'currency' => $validated['custom_currency'] ?? 'SAR',
                    'housing_allowance' => $validated['custom_housing_allowance'] ?? 0,
                    'food_allowance' => $validated['custom_food_allowance'] ?? 0,
                    'transport_allowance' => $validated['custom_transport_allowance'] ?? 0,
                    'other_allowance' => $validated['custom_other_allowance'] ?? 0,
                ];
            }

            $this->employerService->assignCandidate(
                $employer,
                $candidate,
                $validated['employment_type'],
                $customPackage
            );

            return back()->with('success', 'Candidate assigned to employer successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * View candidates for an employer.
     */
    public function candidates(Employer $employer)
    {
        $this->authorize('view', $employer);

        $employer->load(['country']);
        $candidates = $this->employerService->getEmployerCandidates($employer);

        return view('admin.employers.candidates', compact('employer', 'candidates'));
    }

    /**
     * Build default package array from request fields.
     */
    protected function buildPackageFromRequest(Request $request): ?array
    {
        if (!$request->filled('package_base_salary')) {
            return null;
        }

        return [
            'base_salary' => (float) $request->input('package_base_salary', 0),
            'currency' => $request->input('package_currency', 'SAR'),
            'housing_allowance' => (float) $request->input('package_housing_allowance', 0),
            'food_allowance' => (float) $request->input('package_food_allowance', 0),
            'transport_allowance' => (float) $request->input('package_transport_allowance', 0),
            'other_allowance' => (float) $request->input('package_other_allowance', 0),
        ];
    }

    /**
     * Remove package_ prefixed fields from validated data.
     */
    protected function removePackageFields(array $data): array
    {
        return array_filter($data, function ($key) {
            return !str_starts_with($key, 'package_');
        }, ARRAY_FILTER_USE_KEY);
    }
}
