<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Models\Candidate;
use App\Models\Complaint;
use App\Models\Remittance;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\DocumentArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    /**
     * Serve a secure file with authorization check.
     *
     * SECURITY: All private documents must be accessed through this controller
     * to ensure proper authentication and authorization.
     *
     * @param Request $request
     * @param string $path The encrypted/encoded file path
     * @return StreamedResponse|\Illuminate\Http\Response
     */
    public function download(Request $request, string $path)
    {
        // Decode the path (base64 encoded for URL safety)
        $decodedPath = base64_decode($path);

        if (!$decodedPath) {
            abort(404, 'File not found');
        }

        // Security: Prevent directory traversal
        if (Str::contains($decodedPath, ['..', "\0"])) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'attempted_path' => $decodedPath,
                    'ip' => $request->ip(),
                ])
                ->log('Directory traversal attempt blocked');

            abort(403, 'Access denied');
        }

        // Check if file exists on private disk
        if (!Storage::disk('private')->exists($decodedPath)) {
            abort(404, 'File not found');
        }

        // Authorization check based on file path
        if (!$this->authorizeFileAccess($request, $decodedPath)) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'path' => $decodedPath,
                    'ip' => $request->ip(),
                ])
                ->log('Unauthorized file access attempt');

            abort(403, 'You are not authorized to access this file');
        }

        // Log successful access
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'path' => $decodedPath,
                'ip' => $request->ip(),
            ])
            ->log('Secure file downloaded');

        // Get file info
        $mimeType = Storage::disk('private')->mimeType($decodedPath);
        $fileName = basename($decodedPath);

        // Stream the file
        return Storage::disk('private')->download($decodedPath, $fileName, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * View a secure file inline (for PDFs, images).
     *
     * @param Request $request
     * @param string $path
     * @return StreamedResponse|\Illuminate\Http\Response
     */
    public function view(Request $request, string $path)
    {
        $decodedPath = base64_decode($path);

        if (!$decodedPath) {
            abort(404, 'File not found');
        }

        // Security: Prevent directory traversal
        if (Str::contains($decodedPath, ['..', "\0"])) {
            abort(403, 'Access denied');
        }

        if (!Storage::disk('private')->exists($decodedPath)) {
            abort(404, 'File not found');
        }

        if (!$this->authorizeFileAccess($request, $decodedPath)) {
            abort(403, 'You are not authorized to access this file');
        }

        // Log access
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['path' => $decodedPath])
            ->log('Secure file viewed');

        $mimeType = Storage::disk('private')->mimeType($decodedPath);

        return response()->stream(
            function () use ($decodedPath) {
                echo Storage::disk('private')->get($decodedPath);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    /**
     * Authorize file access based on path and user role.
     *
     * @param Request $request
     * @param string $path
     * @return bool
     */
    protected function authorizeFileAccess(Request $request, string $path): bool
    {
        $user = auth()->user();

        // Super admins and project directors can access all files
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return true;
        }

        // Parse the path to determine the resource type
        $parts = explode('/', $path);

        if (count($parts) < 2) {
            return false;
        }

        $resourceType = $parts[0];
        $resourceId = $parts[1] ?? null;

        return match($resourceType) {
            'candidates' => $this->authorizeCandidateFile($user, $resourceId),
            'complaints' => $this->authorizeComplaintFile($user, $resourceId),
            'remittances' => $this->authorizeRemittanceFile($user, $resourceId),
            'visa' => $this->authorizeVisaFile($user, $resourceId),
            'departures' => $this->authorizeDepartureFile($user, $resourceId),
            'documents' => $this->authorizeDocumentFile($user, $resourceId),
            default => false,
        };
    }

    /**
     * Authorize candidate file access.
     */
    protected function authorizeCandidateFile($user, $candidateId): bool
    {
        $candidate = Candidate::find($candidateId);

        if (!$candidate) {
            return false;
        }

        // Campus admin can access their campus's candidates
        if ($user->role === 'campus_admin' && $user->campus_id === $candidate->campus_id) {
            return true;
        }

        // OEP can access their assigned candidates
        if ($user->role === 'oep' && $user->oep_id === $candidate->oep_id) {
            return true;
        }

        // Instructors can access candidates in their training classes
        if ($user->role === 'instructor') {
            return $candidate->batch && $candidate->batch->trainer_id === $user->id;
        }

        return false;
    }

    /**
     * Authorize complaint file access.
     */
    protected function authorizeComplaintFile($user, $complaintId): bool
    {
        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            return false;
        }

        // Campus admin for their campus
        if ($user->role === 'campus_admin' && $user->campus_id === $complaint->campus_id) {
            return true;
        }

        // Assigned user can access
        if ($complaint->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Authorize remittance file access.
     */
    protected function authorizeRemittanceFile($user, $remittanceId): bool
    {
        $remittance = Remittance::with('candidate')->find($remittanceId);

        if (!$remittance || !$remittance->candidate) {
            return false;
        }

        // Campus admin for their campus
        if ($user->role === 'campus_admin' && $user->campus_id === $remittance->candidate->campus_id) {
            return true;
        }

        // OEP for their candidates
        if ($user->role === 'oep' && $user->oep_id === $remittance->candidate->oep_id) {
            return true;
        }

        return false;
    }

    /**
     * Authorize visa file access.
     */
    protected function authorizeVisaFile($user, $visaProcessId): bool
    {
        $visaProcess = VisaProcess::with('candidate')->find($visaProcessId);

        if (!$visaProcess || !$visaProcess->candidate) {
            return false;
        }

        $candidate = $visaProcess->candidate;

        if ($user->role === 'campus_admin' && $user->campus_id === $candidate->campus_id) {
            return true;
        }

        if ($user->role === 'oep' && $user->oep_id === $candidate->oep_id) {
            return true;
        }

        return false;
    }

    /**
     * Authorize departure file access.
     */
    protected function authorizeDepartureFile($user, $departureId): bool
    {
        $departure = Departure::with('candidate')->find($departureId);

        if (!$departure || !$departure->candidate) {
            return false;
        }

        $candidate = $departure->candidate;

        if ($user->role === 'campus_admin' && $user->campus_id === $candidate->campus_id) {
            return true;
        }

        if ($user->role === 'oep' && $user->oep_id === $candidate->oep_id) {
            return true;
        }

        return false;
    }

    /**
     * Authorize document archive file access.
     */
    protected function authorizeDocumentFile($user, $documentId): bool
    {
        $document = DocumentArchive::with('candidate')->find($documentId);

        if (!$document) {
            return false;
        }

        // No candidate attached - check campus
        if (!$document->candidate) {
            return $user->role === 'campus_admin' && $user->campus_id === $document->campus_id;
        }

        $candidate = $document->candidate;

        if ($user->role === 'campus_admin' && $user->campus_id === $candidate->campus_id) {
            return true;
        }

        if ($user->role === 'oep' && $user->oep_id === $candidate->oep_id) {
            return true;
        }

        return false;
    }

    /**
     * Generate a secure URL for a private file.
     *
     * Usage: SecureFileController::secureUrl('candidates/123/cnic.pdf')
     *
     * @param string $path The file path on the private disk
     * @return string The secure download URL
     */
    public static function secureUrl(string $path): string
    {
        return route('secure-file.download', ['path' => base64_encode($path)]);
    }

    /**
     * Generate a secure view URL for a private file.
     *
     * @param string $path The file path on the private disk
     * @return string The secure view URL
     */
    public static function secureViewUrl(string $path): string
    {
        return route('secure-file.view', ['path' => base64_encode($path)]);
    }
}
