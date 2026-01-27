<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Services\PreDepartureDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreDepartureReportController extends Controller
{
    protected PreDepartureDocumentService $service;

    public function __construct(PreDepartureDocumentService $service)
    {
        $this->service = $service;
    }

    /**
     * Generate individual document report for a candidate
     */
    public function individualReport(Candidate $candidate, Request $request)
    {
        $this->authorize('viewAny', [PreDepartureDocument::class, $candidate]);

        $format = $request->get('format', 'pdf'); // pdf or excel

        $path = $this->service->generateIndividualReport($candidate, $format);

        // Log activity
        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'report_type' => 'individual',
                'format' => $format,
            ])
            ->log('Generated pre-departure document report');

        if ($format === 'pdf') {
            return Storage::disk('public')->download($path);
        }

        return Storage::disk('public')->download($path);
    }

    /**
     * Generate bulk document status report
     */
    public function bulkReport(Request $request)
    {
        $filters = $request->only(['campus_id', 'status', 'date_from', 'date_to']);

        $path = $this->service->generateBulkReport($filters);

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'report_type' => 'bulk',
                'filters' => $filters,
            ])
            ->log('Generated bulk pre-departure document report');

        return Storage::disk('public')->download($path);
    }

    /**
     * Generate missing documents report
     */
    public function missingDocumentsReport(Request $request)
    {
        $filters = $request->only(['campus_id']);

        $data = $this->service->generateMissingDocumentsReport($filters);

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'report_type' => 'missing_documents',
                'filters' => $filters,
                'count' => $data->count(),
            ])
            ->log('Generated missing documents report');

        return response()->json([
            'candidates' => $data,
            'total' => $data->count(),
        ]);
    }
}
