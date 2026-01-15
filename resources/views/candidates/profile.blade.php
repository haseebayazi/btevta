@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    @if($candidate->photo_path)
                        <img src="{{ asset('storage/' . $candidate->photo_path) }}" 
                             alt="{{ $candidate->name }}" 
                             class="img-fluid rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded-circle mb-3 mx-auto" 
                             style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                    @endif
                    <h5>{{ $candidate->name }}</h5>
                    <p class="text-muted">TheLeap ID: {{ $candidate->btevta_id }}</p>
                    <span class="badge bg-primary">{{ ucfirst($candidate->status) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Name:</strong><br>
                            {{ $candidate->name }}
                        </div>
                        <div class="col-md-6">
                            <strong>Father Name:</strong><br>
                            {{ $candidate->father_name }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>CNIC:</strong><br>
                            {{ $candidate->cnic }}
                        </div>
                        <div class="col-md-6">
                            <strong>Date of Birth:</strong><br>
                            {{ $candidate->date_of_birth->format('d-m-Y') }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Gender:</strong><br>
                            {{ ucfirst($candidate->gender) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong><br>
                            {{ $candidate->phone }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Email:</strong><br>
                            {{ $candidate->email ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>District:</strong><br>
                            {{ $candidate->district }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Academic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Trade:</strong><br>
                            {{ $candidate->trade->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Campus:</strong><br>
                            {{ $candidate->campus->name ?? 'Not Assigned' }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Batch:</strong><br>
                            {{ $candidate->batch->name ?? 'Not Assigned' }}
                        </div>
                        <div class="col-md-6">
                            <strong>OEP:</strong><br>
                            {{ $candidate->oep->name ?? 'Not Assigned' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remittance Summary -->
            @if($candidate->status === \App\Models\Candidate::STATUS_DEPARTED || $remittanceStats['total_count'] > 0)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave"></i> Remittance Summary
                        </h5>
                        <a href="{{ route('remittances.index', ['candidate_id' => $candidate->id]) }}"
                           class="btn btn-sm btn-light">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Row -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Total Remittances</h6>
                                <h4 class="mb-0 text-success">{{ number_format($remittanceStats['total_count']) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Total Amount</h6>
                                <h4 class="mb-0 text-success">PKR {{ number_format($remittanceStats['total_amount'], 0) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">With Proof</h6>
                                <h4 class="mb-0 text-info">{{ number_format($remittanceStats['with_proof']) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Active Alerts</h6>
                                <h4 class="mb-0 {{ $remittanceStats['unresolved_alerts'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($remittanceStats['unresolved_alerts']) }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    @if($remittanceStats['last_remittance'])
                    <!-- Last Remittance Info -->
                    <div class="alert alert-info mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <strong>Last Remittance:</strong>
                                PKR {{ number_format($remittanceStats['last_remittance']->amount, 2) }}
                                on {{ $remittanceStats['last_remittance']->transfer_date->format('M d, Y') }}
                                <span class="badge bg-{{ $remittanceStats['last_remittance']->status === 'verified' ? 'success' : 'warning' }} ms-2">
                                    {{ ucfirst($remittanceStats['last_remittance']->status) }}
                                </span>
                            </div>
                            <div class="col-md-4 text-end">
                                <small class="text-muted">
                                    {{ $remittanceStats['last_remittance']->transfer_date->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($remittanceStats['total_count'] > 0)
                    <!-- Recent Remittances Table -->
                    <h6 class="mb-3">Recent Remittances</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidate->remittances as $remittance)
                                <tr>
                                    <td>{{ $remittance->transfer_date->format('M d, Y') }}</td>
                                    <td class="fw-bold">PKR {{ number_format($remittance->amount, 0) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $remittance->primary_purpose)) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $remittance->status === 'verified' ? 'success' : ($remittance->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($remittance->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($remittance->has_proof)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-times-circle text-danger"></i>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($remittanceStats['total_count'] > 5)
                    <div class="text-center mt-3">
                        <a href="{{ route('remittances.index', ['candidate_id' => $candidate->id]) }}" class="btn btn-sm btn-outline-success">
                            View All {{ number_format($remittanceStats['total_count']) }} Remittances
                            <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    @endif
                    @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-info-circle"></i> No remittances recorded yet for this candidate.
                    </div>
                    @endif

                    @if($remittanceStats['unresolved_alerts'] > 0)
                    <div class="alert alert-danger mb-0 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>{{ $remittanceStats['unresolved_alerts'] }} active alert(s)</strong> require attention
                            </span>
                            <a href="{{ route('remittance.alerts.index', ['candidate_id' => $candidate->id]) }}"
                               class="btn btn-sm btn-danger">
                                View Alerts
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <a href="{{ route('candidates.edit', $candidate) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('candidates.timeline', $candidate) }}" class="btn btn-info">
                    <i class="fas fa-history"></i> Timeline
                </a>
                <a href="{{ route('candidates.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection