@extends('layouts.app')
@section('title', 'Complaint Statistics')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Complaint Statistics & Analytics</h2>
            <p class="text-muted">Comprehensive overview of complaint trends</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Complaints
            </a>
            <a href="{{ route('complaints.overdue') }}" class="btn btn-danger">
                <i class="fas fa-clock"></i> Overdue
            </a>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3>{{ $totalComplaints }}</h3>
                    <p class="mb-0">Total Complaints</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3>{{ $openComplaints }}</h3>
                    <p class="mb-0">Open/In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3>{{ $resolvedComplaints }}</h3>
                    <p class="mb-0">Resolved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3>{{ $closedComplaints }}</h3>
                    <p class="mb-0">Closed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Breakdowns -->
    <div class="row">
        <!-- By Category -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags"></i> Complaints by Category</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byCategory as $category => $count)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $category)) }}</td>
                                    <td class="text-right"><strong>{{ $count }}</strong></td>
                                    <td class="text-right">
                                        {{ $totalComplaints > 0 ? round(($count / $totalComplaints) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- By Priority -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Complaints by Priority</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byPriority as $priority => $count)
                                <tr>
                                    <td>
                                        <span class="badge badge-{{ $priority === 'urgent' ? 'danger' : ($priority === 'high' ? 'warning' : 'info') }}">
                                            {{ ucfirst($priority) }}
                                        </span>
                                    </td>
                                    <td class="text-right"><strong>{{ $count }}</strong></td>
                                    <td class="text-right">
                                        {{ $totalComplaints > 0 ? round(($count / $totalComplaints) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- By Status -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Complaints by Status</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">%</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byStatus as $status => $count)
                                <tr>
                                    <td>
                                        <span class="badge badge-{{ $status === 'resolved' ? 'success' : ($status === 'closed' ? 'secondary' : 'warning') }}">
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </td>
                                    <td class="text-right"><strong>{{ $count }}</strong></td>
                                    <td class="text-right">
                                        {{ $totalComplaints > 0 ? round(($count / $totalComplaints) * 100, 1) : 0 }}%
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $status === 'resolved' ? 'success' : 'primary' }}"
                                                 style="width: {{ $totalComplaints > 0 ? ($count / $totalComplaints) * 100 : 0 }}%">
                                                {{ $count }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
