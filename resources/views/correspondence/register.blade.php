@extends('layouts.app')
@section('title', 'Correspondence Register')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Correspondence Register</h2>
            <p class="text-muted">Complete register of all correspondence records</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> View List
            </a>
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print Register
            </button>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['total'] ?? 0 }}</h3>
                    <p class="mb-0">Total Records</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['incoming'] ?? 0 }}</h3>
                    <p class="mb-0">Incoming</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['outgoing'] ?? 0 }}</h3>
                    <p class="mb-0">Outgoing</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ $stats['pending_reply'] ?? 0 }}</h3>
                    <p class="mb-0">Pending Reply</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">Filter by Year:</label>
                <select name="year" class="form-control mr-2">
                    <option value="">All Years</option>
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <select name="month" class="form-control mr-2">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request('year') || request('month'))
                    <a href="{{ route('correspondence.register') }}" class="btn btn-secondary ml-2">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Register Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Correspondence Register</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Reference Number</th>
                        <th>Type</th>
                        <th>From/To</th>
                        <th>Subject</th>
                        <th>Campus</th>
                        <th>Reply Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($correspondences as $index => $corr)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $corr->correspondence_date->format('d/m/Y') }}</td>
                            <td class="text-monospace">{{ $corr->reference_number }}</td>
                            <td>
                                <span class="badge badge-{{ $corr->correspondence_type === 'incoming' ? 'info' : 'success' }} badge-sm">
                                    {{ strtoupper(substr($corr->correspondence_type, 0, 3)) }}
                                </span>
                            </td>
                            <td>{{ $corr->from_to }}</td>
                            <td>{{ $corr->subject }}</td>
                            <td>{{ $corr->campus->name ?? 'HQ' }}</td>
                            <td class="text-center">
                                @if($corr->requires_reply)
                                    @if($corr->replied)
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="fas fa-clock text-warning"></i>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No correspondence records found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(isset($correspondences) && method_exists($correspondences, 'links'))
        <div class="mt-3">
            {{ $correspondences->links() }}
        </div>
    @endif
</div>

<style>
@media print {
    .btn, .card-header, .pagination { display: none; }
    .card { border: none; }
    table { font-size: 11px; }
}
</style>
@endsection
