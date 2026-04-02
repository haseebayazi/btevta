<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Candidate;
use App\Models\PostDepartureDetail;
use App\Models\CompanySwitchLog;
use App\Services\PostDepartureService;
use Illuminate\Http\Request;

class PostDepartureController extends Controller
{
    protected PostDepartureService $service;

    public function __construct(PostDepartureService $service)
    {
        $this->service = $service;
    }

    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $campusId = $user->isCampusAdmin() ? $user->campus_id : $request->get('campus_id');

        $dashboard = $this->service->getDashboard($campusId);
        $campuses = Campus::orderBy('name')->get();

        return view('post-departure.dashboard', compact('dashboard', 'campuses'));
    }

    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $detail = $this->service->getOrCreateDetails($candidate);
        $checklist = $detail->getComplianceChecklist();
        $employmentHistory = $detail->employmentHistory;
        $switches = CompanySwitchLog::where('candidate_id', $candidate->id)->get();

        return view('post-departure.show', compact('candidate', 'detail', 'checklist', 'employmentHistory', 'switches'));
    }

    public function updateIqama(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'iqama_number' => 'required|string|max:50',
            'iqama_issue_date' => 'required|date',
            'iqama_expiry_date' => 'required|date|after:iqama_issue_date',
            'iqama_status' => 'required|in:pending,issued,expired,renewed',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->updateIqama($detail, $validated, $request->file('evidence'));

        return back()->with('success', 'Iqama details updated.');
    }

    public function updateForeignContact(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'mobile_number' => 'required|string|max:20',
            'carrier' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $this->service->updateForeignContact($detail, $validated);

        return back()->with('success', 'Foreign contact updated.');
    }

    public function updateForeignBank(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'iban' => 'nullable|string|max:50',
            'swift' => 'nullable|string|max:20',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->updateForeignBank($detail, $validated, $request->file('evidence'));

        return back()->with('success', 'Foreign bank details updated.');
    }

    public function registerTrackingApp(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'app_name' => 'required|string|max:50',
            'app_id' => 'required|string|max:100',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->registerTrackingApp(
            $detail,
            $validated['app_name'],
            $validated['app_id'],
            $request->file('evidence')
        );

        return back()->with('success', 'Tracking app registration recorded.');
    }

    public function registerWPS(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $request->validate([
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $this->service->registerWPS($detail, $request->file('evidence'));

        return back()->with('success', 'WPS registration recorded.');
    }

    public function updateContract(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'contract_number' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:pending,active,completed,terminated',
            'contract_file' => 'nullable|file|max:10240|mimes:pdf',
        ]);

        $this->service->updateContract($detail, $validated, $request->file('contract_file'));

        return back()->with('success', 'Contract details updated.');
    }

    public function addEmployment(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'company_name' => 'required|string|max:200',
            'employer_id' => 'nullable|exists:employers,id',
            'company_address' => 'nullable|string|max:500',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:150',
            'position_title' => 'nullable|string|max:100',
            'work_location' => 'nullable|string|max:200',
            'base_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'housing_allowance' => 'nullable|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'commencement_date' => 'required|date',
            'contract' => 'nullable|file|max:10240|mimes:pdf',
        ]);

        $this->service->addInitialEmployment($detail, $validated, $request->file('contract'));

        return back()->with('success', 'Employment details recorded.');
    }

    public function initiateSwitch(Request $request, PostDepartureDetail $detail)
    {
        $this->authorize('update', $detail->candidate);

        $validated = $request->validate([
            'company_name' => 'required|string|max:200',
            'reason' => 'required|string|max:500',
            'base_salary' => 'required|numeric|min:0',
            'commencement_date' => 'required|date',
            'release_letter' => 'required|file|max:5120|mimes:pdf',
            'new_contract' => 'nullable|file|max:10240|mimes:pdf',
        ]);

        try {
            $this->service->initiateCompanySwitch(
                $detail,
                $validated,
                $validated['reason'],
                $request->file('release_letter'),
                $request->file('new_contract')
            );

            return back()->with('success', 'Company switch initiated. Awaiting approval.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveSwitch(Request $request, CompanySwitchLog $switch)
    {
        $this->authorize('approve', $switch);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'approval_document' => 'nullable|file|max:5120|mimes:pdf',
        ]);

        $this->service->approveCompanySwitch($switch, $request->input('notes'), $request->file('approval_document'));

        return back()->with('success', 'Company switch approved.');
    }

    public function completeSwitch(CompanySwitchLog $switch)
    {
        $this->authorize('complete', $switch);

        try {
            $this->service->completeCompanySwitch($switch);
            return back()->with('success', 'Company switch completed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function verifyCompliance(PostDepartureDetail $detail)
    {
        $this->authorize('verify', $detail);

        try {
            $this->service->verifyCompliance($detail);
            return back()->with('success', '90-day compliance verified successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
