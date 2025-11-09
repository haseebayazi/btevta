@extends('layouts.app')
@section('title', 'Document Archive')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Document Archive</h2>
            <p class="text-muted">Central repository for all archived documents</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('document-archive.expiring') }}" class="btn btn-warning">
                <i class="fas fa-exclamation-triangle"></i> Expiring Documents
            </a>
            <a href="{{ route('document-archive.search') }}" class="btn btn-info">
                <i class="fas fa-search"></i> Advanced Search
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['total'] ?? 0 }}</h3>
                    <p class="mb-0">Total Documents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['active'] ?? 0 }}</h3>
                    <p class="mb-0">Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['expiring_soon'] ?? 0 }}</h3>
                    <p class="mb-0">Expiring Soon</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['expired'] ?? 0 }}</h3>
                    <p class="mb-0">Expired</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" style="width: 300px;"
                       placeholder="Search by document name or reference..." value="{{ request('search') }}">
                <select name="document_type" class="form-control mr-2">
                    <option value="">All Types</option>
                    <option value="cnic" {{ request('document_type') === 'cnic' ? 'selected' : '' }}>CNIC</option>
                    <option value="passport" {{ request('document_type') === 'passport' ? 'selected' : '' }}>Passport</option>
                    <option value="education" {{ request('document_type') === 'education' ? 'selected' : '' }}>Education</option>
                    <option value="police_clearance" {{ request('document_type') === 'police_clearance' ? 'selected' : '' }}>Police Clearance</option>
                    <option value="medical" {{ request('document_type') === 'medical' ? 'selected' : '' }}>Medical</option>
                    <option value="contract" {{ request('document_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                    <option value="other" {{ request('document_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                @if(request('search') || request('document_type'))
                    <a href="{{ route('document-archive.index') }}" class="btn btn-secondary ml-2">Clear</a>
                @endif
            </form>
        </div>
    </div>

    @if($documents->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Archived Documents</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Document Type</th>
                            <th>Reference/Number</th>
                            <th>Candidate</th>
                            <th>Issue Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                            @php
                                $isExpired = $doc->expiry_date && $doc->expiry_date < now();
                                $isExpiringSoon = $doc->expiry_date && $doc->expiry_date->diffInDays(now()) <= 30 && !$isExpired;
                            @endphp
                            <tr class="{{ $isExpired ? 'table-danger' : ($isExpiringSoon ? 'table-warning' : '') }}">
                                <td>
                                    <span class="badge badge-info">
                                        {{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}
                                    </span>
                                </td>
                                <td class="text-monospace">{{ $doc->document_number ?? 'N/A' }}</td>
                                <td>{{ $doc->candidate->name ?? 'N/A' }}</td>
                                <td>{{ $doc->issue_date ? $doc->issue_date->format('Y-m-d') : '-' }}</td>
                                <td>
                                    @if($doc->expiry_date)
                                        {{ $doc->expiry_date->format('Y-m-d') }}
                                        @if($isExpired)
                                            <span class="badge badge-danger ml-1">Expired</span>
                                        @elseif($isExpiringSoon)
                                            <span class="badge badge-warning ml-1">{{ $doc->expiry_date->diffInDays(now()) }} days</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No expiry</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $doc->verification_status === 'verified' ? 'success' : 'warning' }}">
                                        {{ ucfirst($doc->verification_status ?? 'Pending') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ Storage::url($doc->file_path) }}" download class="btn btn-sm btn-success">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $documents->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No documents found in the archive.
        </div>
    @endif
</div>
@endsection
