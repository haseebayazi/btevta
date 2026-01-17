@extends('layouts.app')
@section('title', 'Candidate Screening')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Candidate Screening</h2>
            <p class="text-muted">Manage candidate screening calls and outcomes</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('screening.pending') }}" class="btn btn-warning">
                <i class="fas fa-clock"></i> Pending Screenings
            </a>
            <a href="{{ route('screening.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Log Screening
            </a>
        </div>
    </div>

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Search by name or TheLeap ID..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                @if(request('search'))
                    <a href="{{ route('screening.index') }}" class="btn btn-secondary ml-2">Clear</a>
                @endif
            </form>
        </div>
    </div>

    @if($screenings->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Screening Records</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Candidate</th>
                            <th>TheLeap ID</th>
                            <th>Screening Date</th>
                            <th>Duration (min)</th>
                            <th>Outcome</th>
                            <th>Screened By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($screenings as $screening)
                            <tr>
                                <td>{{ $screening->candidate->name }}</td>
                                <td class="text-monospace">{{ $screening->candidate->btevta_id }}</td>
                                <td>{{ $screening->screened_at ? $screening->screened_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>{{ $screening->call_duration ?? '-' }}</td>
                                <td>
                                    @php
                                        $screeningStatusEnum = \App\Enums\ScreeningStatus::tryFrom($screening->status ?? '');
                                        $screeningColor = $screeningStatusEnum ? $screeningStatusEnum->color() : 'warning';
                                        $screeningLabel = $screeningStatusEnum ? $screeningStatusEnum->label() : 'Pending';
                                    @endphp
                                    <span class="badge badge-{{ $screeningColor }}">
                                        {{ $screeningLabel }}
                                    </span>
                                </td>
                                <td>{{ $screening->screener->name ?? 'N/A' }}</td>
                                <td>
                                    @if($screening->remarks)
                                        <button class="btn btn-sm btn-info" data-toggle="tooltip"
                                                title="{{ $screening->remarks }}">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $screenings->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No screening records found.
        </div>
    @endif
</div>
@endsection
