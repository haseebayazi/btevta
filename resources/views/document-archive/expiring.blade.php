@extends('layouts.app')
@section('title', 'Expiring Documents')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Expiring Documents</h2>
            <p class="text-muted">Documents nearing expiration or already expired</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('document-archive.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Documents
            </a>
            <a href="{{ route('document-archive.search') }}" class="btn btn-info">
                <i class="fas fa-search"></i> Search
            </a>
        </div>
    </div>

    <!-- Alert Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-times-circle"></i> Expired Documents</h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-danger">{{ $expiredCount ?? 0 }}</h2>
                    <p class="mb-0">Documents have already expired and require immediate action</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-warning">{{ $expiringSoonCount ?? 0 }}</h2>
                    <p class="mb-0">Documents expiring within the next 30 days</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter by Days -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">Show documents expiring in:</label>
                <select name="days" class="form-control mr-2">
                    <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Next 30 days</option>
                    <option value="60" {{ request('days') == 60 ? 'selected' : '' }}>Next 60 days</option>
                    <option value="90" {{ request('days') == 90 ? 'selected' : '' }}>Next 90 days</option>
                    <option value="expired" {{ request('days') === 'expired' ? 'selected' : '' }}>Already Expired</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <!-- Expired Documents Section -->
    @if(isset($expiredDocuments) && $expiredDocuments->count())
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-times-circle"></i> Expired Documents ({{ $expiredDocuments->count() }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Type</th>
                            <th>Number</th>
                            <th>Candidate</th>
                            <th>Expiry Date</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiredDocuments as $doc)
                            <tr class="table-danger">
                                <td><span class="badge badge-danger">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</span></td>
                                <td class="text-monospace">{{ $doc->document_number ?? 'N/A' }}</td>
                                <td>
                                    <strong>{{ $doc->candidate->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $doc->candidate->btevta_id ?? '' }}</small>
                                </td>
                                <td>{{ $doc->expiry_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-circle"></i> {{ now()->diffInDays($doc->expiry_date) }} days
                                    </span>
                                </td>
                                <td><span class="badge badge-secondary">{{ ucfirst($doc->verification_status ?? 'Pending') }}</span></td>
                                <td>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($doc->candidate)
                                        <a href="{{ route('candidates.show', $doc->candidate->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-user"></i> Candidate
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Expiring Soon Documents Section -->
    @if(isset($expiringSoonDocuments) && $expiringSoonDocuments->count())
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Expiring Soon ({{ $expiringSoonDocuments->count() }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Type</th>
                            <th>Number</th>
                            <th>Candidate</th>
                            <th>Expiry Date</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiringSoonDocuments as $doc)
                            @php
                                $daysRemaining = now()->diffInDays($doc->expiry_date);
                            @endphp
                            <tr class="{{ $daysRemaining <= 7 ? 'table-warning' : '' }}">
                                <td><span class="badge badge-warning">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</span></td>
                                <td class="text-monospace">{{ $doc->document_number ?? 'N/A' }}</td>
                                <td>
                                    <strong>{{ $doc->candidate->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $doc->candidate->btevta_id ?? '' }}</small>
                                </td>
                                <td>{{ $doc->expiry_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge badge-{{ $daysRemaining <= 7 ? 'danger' : 'warning' }}">
                                        <i class="fas fa-clock"></i> {{ $daysRemaining }} days
                                    </span>
                                </td>
                                <td><span class="badge badge-info">{{ ucfirst($doc->verification_status ?? 'Pending') }}</span></td>
                                <td>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($doc->candidate)
                                        <a href="{{ route('candidates.show', $doc->candidate->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-user"></i> Candidate
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if((!isset($expiredDocuments) || !$expiredDocuments->count()) && (!isset($expiringSoonDocuments) || !$expiringSoonDocuments->count()))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> No expiring or expired documents found. All documents are valid!
        </div>
    @endif
</div>
@endsection
