@extends('layouts.app')
@section('title', 'Registration Management')
@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-1">Registration Management</h2>
            <p class="text-muted mb-0">Module 3: Allocate screened candidates to campus, program, trade, and course</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    {{-- Dashboard Status Cards --}}
    @php
        $allCandidates = $candidates instanceof \Illuminate\Pagination\LengthAwarePaginator ? $candidates->getCollection() : $candidates;
        $screenedCount = $allCandidates->whereIn('status', ['screened', 'screening_passed'])->count();
        $registeredCount = $allCandidates->where('status', 'registered')->count();
        $pendingCount = $allCandidates->where('status', 'pending_registration')->count();
        $totalCount = $allCandidates->count();
    @endphp
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #4e73df;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total in Registration</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $totalCount }}</div>
                        </div>
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #f6c23e;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Ready for Registration</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $screenedCount }}</div>
                        </div>
                        <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #1cc88a;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Registered</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $registeredCount }}</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #36b9cc;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pending</div>
                            <div class="h4 mb-0 font-weight-bold">{{ $pendingCount }}</div>
                        </div>
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Screened Candidates: Ready for Registration --}}
    @php
        $screenedCandidates = $allCandidates->whereIn('status', ['screened', 'screening_passed']);
    @endphp
    @if($screenedCandidates->count() > 0)
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-user-check mr-2"></i>Screened Candidates - Ready for Registration ({{ $screenedCandidates->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">TheLeap ID</th>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($screenedCandidates as $candidate)
                        <tr>
                            <td class="px-4 text-monospace font-weight-bold">{{ $candidate->btevta_id }}</td>
                            <td>{{ $candidate->name }}</td>
                            <td class="text-monospace">{{ $candidate->cnic ?? '-' }}</td>
                            <td>{{ $candidate->campus?->name ?? 'N/A' }}</td>
                            <td>{{ $candidate->trade?->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-warning px-3 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('registration.allocation', $candidate->id) }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-clipboard-list mr-1"></i>Register
                                </a>
                                <a href="{{ route('candidates.show', $candidate->id) }}" class="btn btn-outline-info btn-sm" title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Registered Candidates --}}
    @php
        $registeredCandidates = $allCandidates->where('status', 'registered');
    @endphp
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle mr-2"></i>Registered Candidates ({{ $registeredCandidates->count() }})</h5>
        </div>
        <div class="card-body p-0">
            @if($registeredCandidates->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">TheLeap ID</th>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Batch</th>
                            <th>Allocated #</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registeredCandidates as $candidate)
                        <tr>
                            <td class="px-4 text-monospace font-weight-bold">{{ $candidate->btevta_id }}</td>
                            <td>{{ $candidate->name }}</td>
                            <td class="text-monospace">{{ $candidate->cnic ?? '-' }}</td>
                            <td>{{ $candidate->campus?->name ?? 'N/A' }}</td>
                            <td>{{ $candidate->trade?->name ?? 'N/A' }}</td>
                            <td class="text-monospace small">{{ $candidate->batch?->batch_code ?? $candidate->batch?->name ?? '-' }}</td>
                            <td class="text-monospace font-weight-bold text-success">{{ $candidate->allocated_number ?? '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('registration.show', $candidate->id) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block text-gray-300"></i>
                <p>No registered candidates yet. Use the allocation form to register screened candidates.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Pending Registration Candidates --}}
    @php
        $pendingCandidates = $allCandidates->where('status', 'pending_registration');
    @endphp
    @if($pendingCandidates->count() > 0)
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-clock mr-2"></i>Pending Registration ({{ $pendingCandidates->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">TheLeap ID</th>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingCandidates as $candidate)
                        <tr>
                            <td class="px-4 text-monospace font-weight-bold">{{ $candidate->btevta_id }}</td>
                            <td>{{ $candidate->name }}</td>
                            <td class="text-monospace">{{ $candidate->cnic ?? '-' }}</td>
                            <td>{{ $candidate->campus?->name ?? 'N/A' }}</td>
                            <td>{{ $candidate->trade?->name ?? 'N/A' }}</td>
                            <td><span class="badge badge-info px-3 py-1">Pending</span></td>
                            <td class="text-center">
                                <a href="{{ route('registration.show', $candidate->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit mr-1"></i>Manage
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if($totalCount === 0)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-gray-300 mb-3"></i>
            <h4 class="text-muted">No Candidates in Registration Phase</h4>
            <p class="text-muted">Candidates will appear here once they complete the screening process (Module 2).</p>
        </div>
    </div>
    @endif
</div>
@endsection
