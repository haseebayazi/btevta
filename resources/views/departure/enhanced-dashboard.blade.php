@extends('layouts.app')
@section('title', 'Enhanced Departure Dashboard')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-plane-departure"></i> Enhanced Departure Dashboard</h2>
            <p class="text-muted">Structured status tracking for PTN, Protector, Ticket, and Briefing</p>
        </div>
        <div class="col-md-4 text-right">
            @can('viewAny', \App\Models\Departure::class)
            <form method="GET" class="d-inline-flex">
                <select name="campus_id" class="form-control mr-2" onchange="this.form.submit()">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                    <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                        {{ $campus->name }}
                    </option>
                    @endforeach
                </select>
            </form>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h4>{{ $dashboard['summary']['total'] }}</h4>
                    <p class="mb-0">Total Departures</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4>{{ $dashboard['summary']['processing'] }}</h4>
                    <p class="mb-0"><i class="fas fa-cog"></i> Processing</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4>{{ $dashboard['summary']['ready_to_depart'] }}</h4>
                    <p class="mb-0"><i class="fas fa-plane-departure"></i> Ready to Depart</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4>{{ $dashboard['summary']['departed'] }}</h4>
                    <p class="mb-0"><i class="fas fa-plane"></i> Departed</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4>{{ $dashboard['summary']['cancelled'] }}</h4>
                    <p class="mb-0"><i class="fas fa-ban"></i> Cancelled</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdowns -->
    <div class="row mb-4">
        <!-- PTN Status -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>PTN Status</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-circle text-secondary"></i> Pending</span>
                        <span class="badge badge-secondary">{{ $dashboard['ptn_status']['pending'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="fas fa-check-circle text-success"></i> Issued</span>
                        <span class="badge badge-success">{{ $dashboard['ptn_status']['issued'] }}</span>
                    </div>
                    @php $ptnTotal = $dashboard['ptn_status']['pending'] + $dashboard['ptn_status']['issued']; @endphp
                    @if($ptnTotal > 0)
                    <div class="progress mt-3">
                        <div class="progress-bar bg-success"
                             style="width: {{ round(($dashboard['ptn_status']['issued'] / $ptnTotal) * 100) }}%">
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Protector Status -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Protector Status</strong></div>
                <div class="card-body">
                    @foreach($dashboard['protector_status'] as $key => $count)
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                        <span class="badge badge-secondary">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Briefing Status -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Pre-Departure Briefing Status</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-calendar text-secondary"></i> Not Scheduled</span>
                        <span class="badge badge-secondary">{{ $dashboard['briefing_status']['not_scheduled'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-calendar-check text-info"></i> Scheduled</span>
                        <span class="badge badge-info">{{ $dashboard['briefing_status']['scheduled'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="fas fa-check-circle text-success"></i> Completed</span>
                        <span class="badge badge-success">{{ $dashboard['briefing_status']['completed'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ready to Depart -->
    @if($dashboard['ready_candidates']->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <strong><i class="fas fa-plane-departure"></i> Ready to Depart ({{ $dashboard['ready_candidates']->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Trade</th>
                        <th>Campus</th>
                        <th>Flight</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dashboard['ready_candidates'] as $departure)
                    <tr>
                        <td>{{ $departure->candidate->name ?? '-' }}</td>
                        <td>{{ $departure->candidate->trade->name ?? '-' }}</td>
                        <td>{{ $departure->candidate->campus->name ?? '-' }}</td>
                        <td>
                            @if($departure->ticket_details_object->airline)
                            {{ $departure->ticket_details_object->airline }}
                            {{ $departure->ticket_details_object->flightNumber }}
                            <br><small class="text-muted">{{ $departure->ticket_details_object->departureDate }}</small>
                            @else
                            <span class="text-muted">No ticket</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('departure.checklist', $departure) }}" class="btn btn-sm btn-outline-primary">
                                Checklist
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Upcoming Flights -->
    @if($dashboard['upcoming_flights']->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <strong><i class="fas fa-plane"></i> Upcoming Flights (Next 10)</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Airline</th>
                        <th>Flight</th>
                        <th>Departure Date</th>
                        <th>From → To</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dashboard['upcoming_flights'] as $departure)
                    <tr>
                        <td>{{ $departure->candidate->name ?? '-' }}</td>
                        <td>{{ $departure->ticket_details_object->airline ?? '-' }}</td>
                        <td>{{ $departure->ticket_details_object->flightNumber ?? '-' }}</td>
                        <td>
                            {{ $departure->ticket_details_object->departureDate ?? '-' }}
                            <br><small>{{ $departure->ticket_details_object->departureTime ?? '' }}</small>
                        </td>
                        <td>
                            {{ $departure->ticket_details_object->departureAirport ?? '-' }}
                            →
                            {{ $departure->ticket_details_object->arrivalAirport ?? '-' }}
                        </td>
                        <td>
                            @if($departure->departure_status)
                            <span class="badge badge-{{ $departure->departure_status->color() }}">
                                {{ $departure->departure_status->label() }}
                            </span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('departure.checklist', $departure) }}" class="btn btn-sm btn-outline-primary">
                                Checklist
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
