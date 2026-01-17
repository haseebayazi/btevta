@extends('layouts.app')
@section('title', 'Pending Screening')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Pending Screenings</h2>
            <p class="text-muted">Candidates awaiting screening completion</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('screening.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Screenings
            </a>
            <a href="{{ route('screening.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Log Screening
            </a>
        </div>
    </div>

    @if($candidates->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Candidates Pending Screening ({{ $candidates->count() }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>TheLeap ID</th>
                            <th>Name</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Screenings Completed</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidates as $candidate)
                            <tr>
                                <td class="text-monospace">{{ $candidate->btevta_id }}</td>
                                <td>{{ $candidate->name }}</td>
                                <td>{{ $candidate->campus->name ?? 'N/A' }}</td>
                                <td>{{ $candidate->trade->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-warning">
                                        {{ $candidate->screenings_count }}/3
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ ucfirst($candidate->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('candidates.show', $candidate->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('screening.create') }}?candidate_id={{ $candidate->id }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-phone"></i> Log Call
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> No pending screenings! All candidates have completed their screening calls.
        </div>
    @endif
</div>
@endsection
