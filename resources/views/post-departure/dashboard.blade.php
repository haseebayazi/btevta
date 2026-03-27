@extends('layouts.app')
@section('title', 'Post-Departure Dashboard')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-globe"></i> Post-Departure Dashboard</h2>
            <p class="text-muted">Comprehensive post-departure tracking: residency, employment, and compliance</p>
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
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Candidates</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dashboard['summary']['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Compliance Verified</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dashboard['summary']['compliance_verified'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Compliance Pending</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dashboard['summary']['compliance_pending'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="row mb-4">
        <!-- Iqama Status -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card"></i> Iqama Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge badge-warning">Pending: {{ $dashboard['iqama_status']['pending'] }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="badge badge-success">Issued: {{ $dashboard['iqama_status']['issued'] }}</span>
                    </div>
                    <div>
                        <span class="badge badge-danger">Expiring Soon: {{ $dashboard['iqama_status']['expiring'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking App -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-mobile-alt"></i> Tracking App (Absher)</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge badge-success">Registered: {{ $dashboard['tracking_app']['registered'] }}</span>
                    </div>
                    <div>
                        <span class="badge badge-warning">Pending: {{ $dashboard['tracking_app']['pending'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- WPS -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-money-check-alt"></i> WPS Registration</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge badge-success">Registered: {{ $dashboard['wps']['registered'] }}</span>
                    </div>
                    <div>
                        <span class="badge badge-warning">Pending: {{ $dashboard['wps']['pending'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Company Switches -->
    @if($dashboard['recent_switches']->isNotEmpty())
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-exchange-alt"></i> Recent Company Switches</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>From Company</th>
                            <th>To Company</th>
                            <th>Switch #</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dashboard['recent_switches'] as $switch)
                        <tr>
                            <td>{{ $switch->candidate?->name ?? 'N/A' }}</td>
                            <td>{{ $switch->fromEmployment?->company_name ?? 'N/A' }}</td>
                            <td>{{ $switch->toEmployment?->company_name ?? 'N/A' }}</td>
                            <td>{{ $switch->switch_number }}</td>
                            <td><span class="badge badge-{{ $switch->status->color() }}">{{ $switch->status->label() }}</span></td>
                            <td>{{ $switch->switch_date->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
