@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Pre-Departure Documents</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}">Candidates</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('candidates.show', $candidate) }}">{{ $candidate->name }}</a></li>
                            <li class="breadcrumb-item active">Pre-Departure Documents</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Candidate
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Candidate Info Card --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Candidate:</strong> {{ $candidate->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>BTEVTA ID:</strong> {{ $candidate->btevta_id }}
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong> 
                            <span class="badge badge-{{ $candidate->status === 'new' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($candidate->status) }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Documents:</strong>
                            <span class="badge badge-{{ $status['is_complete'] ? 'success' : 'warning' }}">
                                {{ $status['mandatory_uploaded'] }}/{{ $status['mandatory_total'] }} ({{ $status['completion_percentage'] }}%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert if documents incomplete --}}
    @if(!$status['is_complete'])
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Action Required:</strong> This candidate cannot proceed to screening until all mandatory documents are uploaded.
        @if($candidate->getMissingMandatoryDocuments()->isNotEmpty())
            Missing: <strong>{{ $candidate->getMissingMandatoryDocuments()->pluck('name')->implode(', ') }}</strong>
        @endif
    </div>
    @else
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <strong>Complete:</strong> All mandatory pre-departure documents have been uploaded.
    </div>
    @endif

    {{-- Alert if read-only mode --}}
    @if($candidate->status !== 'new')
    <div class="alert alert-info">
        <i class="fas fa-lock"></i>
        <strong>Read-Only Mode:</strong> Documents cannot be edited because the candidate has progressed past the 'new' status.
    </div>
    @endif

    {{-- Mandatory Documents Section --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle"></i> Mandatory Documents (Required)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($checklists->where('is_mandatory', true) as $checklist)
                            @php
                                $document = $documents->firstWhere('document_checklist_id', $checklist->id);
                            @endphp
                            <div class="col-md-6 mb-3">
                                @include('candidates.pre-departure-documents.partials.document-card', [
                                    'checklist' => $checklist,
                                    'document' => $document,
                                    'candidate' => $candidate
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Optional Documents Section --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Optional Documents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($checklists->where('is_mandatory', false) as $checklist)
                            @php
                                $document = $documents->firstWhere('document_checklist_id', $checklist->id);
                            @endphp
                            <div class="col-md-6 mb-3">
                                @include('candidates.pre-departure-documents.partials.document-card', [
                                    'checklist' => $checklist,
                                    'document' => $document,
                                    'candidate' => $candidate
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Licenses Section --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-id-card"></i> Licenses (Driving & Professional)
                        </h5>
                        @can('create', [App\Models\CandidateLicense::class, $candidate])
                            <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#addLicenseModal">
                                <i class="fas fa-plus"></i> Add License
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($licenses->isEmpty())
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-info-circle"></i> No licenses added yet.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>Number</th>
                                        <th>Category</th>
                                        <th>Issue Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($licenses as $license)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $license->license_type === 'driving' ? 'primary' : 'success' }}">
                                                {{ ucfirst($license->license_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $license->license_name }}</td>
                                        <td><code>{{ $license->license_number }}</code></td>
                                        <td>{{ $license->license_category ?? 'N/A' }}</td>
                                        <td>{{ $license->issue_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                        <td>{{ $license->expiry_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                        <td>
                                            @if($license->isExpired())
                                                <span class="badge badge-danger">Expired</span>
                                            @elseif($license->isExpiringSoon())
                                                <span class="badge badge-warning">Expiring Soon</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>
                                        <td>
                                            @can('delete', $license)
                                                <form action="{{ route('candidates.licenses.destroy', [$candidate, $license]) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this license?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Report Actions --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-download"></i> Generate Reports</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="{{ route('reports.pre-departure.individual', ['candidate' => $candidate, 'format' => 'pdf']) }}" 
                           class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf"></i> Download PDF Report
                        </a>
                        <a href="{{ route('reports.pre-departure.individual', ['candidate' => $candidate, 'format' => 'excel']) }}" 
                           class="btn btn-outline-success">
                            <i class="fas fa-file-excel"></i> Download Excel Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add License Modal --}}
@include('candidates.pre-departure-documents.partials.add-license-modal')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-hide success alerts after 5 seconds
    setTimeout(function() {
        $('.alert-success').fadeOut('slow');
    }, 5000);
});
</script>
@endpush
