@extends('layouts.app')
@section('title', 'Advanced Document Search')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Advanced Document Search</h2>
            <p class="text-muted">Search archived documents with multiple filters</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('document-archive.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Archive
            </a>
        </div>
    </div>

    <!-- Advanced Search Form -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-search"></i> Search Criteria</h5>
        </div>
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="cnic" {{ request('document_type') === 'cnic' ? 'selected' : '' }}>CNIC</option>
                                <option value="passport" {{ request('document_type') === 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="education" {{ request('document_type') === 'education' ? 'selected' : '' }}>Education Certificate</option>
                                <option value="police_clearance" {{ request('document_type') === 'police_clearance' ? 'selected' : '' }}>Police Clearance</option>
                                <option value="medical" {{ request('document_type') === 'medical' ? 'selected' : '' }}>Medical Certificate</option>
                                <option value="contract" {{ request('document_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                                <option value="visa" {{ request('document_type') === 'visa' ? 'selected' : '' }}>Visa</option>
                                <option value="other" {{ request('document_type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Verification Status</label>
                            <select name="verification_status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('verification_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="verified" {{ request('verification_status') === 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ request('verification_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Document Number</label>
                            <input type="text" name="document_number" class="form-control"
                                   placeholder="Enter document number" value="{{ request('document_number') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Candidate Name</label>
                            <input type="text" name="candidate_name" class="form-control"
                                   placeholder="Search by candidate name" value="{{ request('candidate_name') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Issue Date From</label>
                            <input type="date" name="issue_date_from" class="form-control" value="{{ request('issue_date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Issue Date To</label>
                            <input type="date" name="issue_date_to" class="form-control" value="{{ request('issue_date_to') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Expiry Status</label>
                            <select name="expiry_status" class="form-control">
                                <option value="">All Documents</option>
                                <option value="active" {{ request('expiry_status') === 'active' ? 'selected' : '' }}>Active (Not Expired)</option>
                                <option value="expiring_soon" {{ request('expiry_status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (30 days)</option>
                                <option value="expired" {{ request('expiry_status') === 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="no_expiry" {{ request('expiry_status') === 'no_expiry' ? 'selected' : '' }}>No Expiry Date</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Expiry Date From</label>
                            <input type="date" name="expiry_date_from" class="form-control" value="{{ request('expiry_date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Expiry Date To</label>
                            <input type="date" name="expiry_date_to" class="form-control" value="{{ request('expiry_date_to') }}">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('document-archive.search') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-search"></i> Search Documents
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    @if(request()->hasAny(['document_type', 'verification_status', 'document_number', 'candidate_name', 'issue_date_from', 'issue_date_to', 'expiry_status', 'expiry_date_from', 'expiry_date_to']))
        @if(isset($documents) && $documents->count())
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle"></i> Search Results ({{ $documents->total() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Type</th>
                                <th>Number</th>
                                <th>Candidate</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $doc)
                                <tr>
                                    <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</span></td>
                                    <td class="text-monospace">{{ $doc->document_number ?? 'N/A' }}</td>
                                    <td>{{ $doc->candidate->name ?? 'N/A' }}</td>
                                    <td>{{ $doc->issue_date ? $doc->issue_date->format('Y-m-d') : '-' }}</td>
                                    <td>{{ $doc->expiry_date ? $doc->expiry_date->format('Y-m-d') : 'No expiry' }}</td>
                                    <td><span class="badge badge-{{ $doc->verification_status === 'verified' ? 'success' : 'warning' }}">{{ ucfirst($doc->verification_status ?? 'Pending') }}</span></td>
                                    <td>
                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">
                {{ $documents->appends(request()->query())->links() }}
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle"></i> No documents found matching your search criteria. Try adjusting the filters.
            </div>
        @endif
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Enter search criteria above to find documents.
        </div>
    @endif
</div>
@endsection
