@extends('layouts.app')
@section('title', 'Pending Compliance')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Pending Compliance</h2>
            <p class="text-muted">Departures awaiting compliance verification</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('departure.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Departures
            </a>
        </div>
    </div>

    @if(isset($pendingCount) && $pendingCount > 0)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>{{ $pendingCount }}</strong> departure(s) pending compliance verification.
        </div>
    @endif

    @if(isset($pendingDepartures) && $pendingDepartures->count())
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Compliance Verification</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Candidate</th>
                            <th>TheLeap ID</th>
                            <th>Trade</th>
                            <th>Destination</th>
                            <th>Departure Date</th>
                            <th>Days Since Departure</th>
                            <th>Required Documents</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingDepartures as $departure)
                            @php
                                $daysSinceDeparture = $departure->departure_date ? now()->diffInDays($departure->departure_date) : null;
                                $isOverdue = $daysSinceDeparture && $daysSinceDeparture > 30;
                            @endphp
                            <tr class="{{ $isOverdue ? 'table-danger' : 'table-warning' }}">
                                <td>
                                    <strong>{{ $departure->candidate->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $departure->candidate->phone ?? '' }}</small>
                                </td>
                                <td class="text-monospace">{{ $departure->candidate->btevta_id ?? 'N/A' }}</td>
                                <td>{{ $departure->candidate->trade->name ?? 'N/A' }}</td>
                                <td>
                                    <strong>{{ $departure->destination_country ?? 'N/A' }}</strong>
                                    @if($departure->oep)
                                        <br><small class="text-muted">{{ $departure->oep->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($departure->departure_date)
                                        {{ $departure->departure_date->format('Y-m-d') }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($daysSinceDeparture !== null)
                                        @if($isOverdue)
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-circle"></i> {{ $daysSinceDeparture }} days (Overdue)
                                            </span>
                                        @else
                                            <span class="badge badge-warning">{{ $daysSinceDeparture }} days</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <ul class="list-unstyled mb-0 small">
                                        <li>
                                            @if($departure->arrival_confirmation)
                                                <i class="fas fa-check-circle text-success"></i> Arrival Confirmation
                                            @else
                                                <i class="fas fa-times-circle text-danger"></i> Arrival Confirmation
                                            @endif
                                        </li>
                                        <li>
                                            @if($departure->employment_contract)
                                                <i class="fas fa-check-circle text-success"></i> Employment Contract
                                            @else
                                                <i class="fas fa-times-circle text-danger"></i> Employment Contract
                                            @endif
                                        </li>
                                        <li>
                                            @if($departure->welfare_check)
                                                <i class="fas fa-check-circle text-success"></i> Welfare Check
                                            @else
                                                <i class="fas fa-times-circle text-danger"></i> Welfare Check
                                            @endif
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <a href="{{ route('candidates.show', $departure->candidate_id) }}" class="btn btn-sm btn-primary mb-1">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form action="{{ route('departure.mark-compliant', $departure->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success mb-1" onclick="return confirm('Mark this departure as compliant?')">
                                            <i class="fas fa-check"></i> Mark Compliant
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-info-circle"></i> Compliance Requirements:</h6>
                    <ul class="mb-0">
                        <li><strong>Arrival Confirmation:</strong> Official confirmation of candidate's arrival in destination country</li>
                        <li><strong>Employment Contract:</strong> Signed employment contract with OEP/employer</li>
                        <li><strong>Welfare Check:</strong> Initial welfare assessment completed within 30 days of departure</li>
                    </ul>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> Departures over 30 days without compliance verification are marked as overdue and require immediate action.
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> No departures pending compliance! All departures have been verified.
        </div>
    @endif
</div>
@endsection
