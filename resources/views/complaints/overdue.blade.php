@extends('layouts.app')
@section('title', 'Overdue Complaints')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Overdue Complaints</h2>
            <p class="text-muted">Complaints that have exceeded SLA deadlines</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Complaints
            </a>
            <a href="{{ route('complaints.statistics') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Statistics
            </a>
        </div>
    </div>

    @if($overdueComplaints->count())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <strong>{{ $overdueComplaints->count() }}</strong> complaints are overdue. Please take immediate action!
        </div>

        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Overdue Complaint List</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Complainant</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th>Registered</th>
                            <th>SLA Deadline</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueComplaints as $complaint)
                            @php
                                $slaDeadline = $complaint->registered_at->addDays($complaint->sla_days ?? 7);
                                $daysOverdue = now()->diffInDays($slaDeadline);
                            @endphp
                            <tr class="table-danger">
                                <td><strong>#{{ $complaint->id }}</strong></td>
                                <td>{{ $complaint->candidate->name ?? $complaint->complainant_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $complaint->category)) }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($complaint->subject ?? $complaint->description, 40) }}</td>
                                <td>{{ $complaint->registered_at->format('Y-m-d') }}</td>
                                <td>{{ $slaDeadline->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-circle"></i> {{ $daysOverdue }} days
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-warning">
                                        {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('complaints.show', $complaint->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($complaint->status !== 'resolved')
                                        <form action="{{ route('complaints.assign', $complaint->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="fas fa-user-plus"></i> Assign
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> No overdue complaints! All complaints are within SLA deadlines.
        </div>
    @endif
</div>
@endsection
