@extends('layouts.app')
@section('title', 'Departure Management')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Departure Management</h2>
            <p class="text-muted">Track candidate departures and compliance</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('departure.pending-compliance') }}" class="btn btn-warning">
                <i class="fas fa-exclamation-triangle"></i> Pending Compliance
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['total_departures'] ?? 0 }}</h3>
                    <p class="mb-0">Total Departures</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['completed'] ?? 0 }}</h3>
                    <p class="mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['pending_compliance'] ?? 0 }}</h3>
                    <p class="mb-0">Pending Compliance</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['this_month'] ?? 0 }}</h3>
                    <p class="mb-0">This Month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" style="width: 250px;"
                       placeholder="Search by name or ID..." value="{{ request('search') }}">
                <select name="status" class="form-control mr-2">
                    <option value="">All Statuses</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="departed" {{ request('status') === 'departed' ? 'selected' : '' }}>Departed</option>
                    <option value="pending_compliance" {{ request('status') === 'pending_compliance' ? 'selected' : '' }}>Pending Compliance</option>
                    <option value="compliant" {{ request('status') === 'compliant' ? 'selected' : '' }}>Compliant</option>
                </select>
                <select name="month" class="form-control mr-2">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                @if(request()->hasAny(['search', 'status', 'month']))
                    <a href="{{ route('departure.index') }}" class="btn btn-secondary ml-2">Clear</a>
                @endif
            </form>
        </div>
    </div>

    @if(isset($departures) && $departures->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Departure Records</h5>
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
                            <th>Flight Details</th>
                            <th>Compliance Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departures as $departure)
                            <tr>
                                <td>
                                    <strong>{{ $departure->candidate->name ?? 'N/A' }}</strong>
                                    <br><small class="text-muted">{{ $departure->candidate->passport_number ?? '' }}</small>
                                </td>
                                <td class="text-monospace">{{ $departure->candidate->btevta_id ?? 'N/A' }}</td>
                                <td>{{ $departure->candidate->trade->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $departure->destination_country ?? 'N/A' }}
                                    @if($departure->oep)
                                        <br><small class="text-muted">{{ $departure->oep->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($departure->departure_date)
                                        {{ $departure->departure_date->format('Y-m-d') }}
                                        <br><small class="text-muted">{{ $departure->departure_date->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">Not scheduled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($departure->flight_number)
                                        <strong>{{ $departure->flight_number }}</strong>
                                        <br><small class="text-muted">{{ $departure->airline ?? '' }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($departure->compliance_status === 'compliant')
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Compliant
                                        </span>
                                    @elseif($departure->compliance_status === 'pending')
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @elseif($departure->compliance_status === 'non_compliant')
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times-circle"></i> Non-Compliant
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Not Assessed</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('candidates.show', $departure->candidate_id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($departure->compliance_status === 'pending')
                                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#complianceModal{{ $departure->id }}">
                                            <i class="fas fa-check"></i> Mark Compliant
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $departures->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No departure records found.
        </div>
    @endif
</div>
@endsection
