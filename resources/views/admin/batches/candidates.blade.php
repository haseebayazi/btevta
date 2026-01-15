@extends('layouts.app')
@section('title', 'Batch Candidates - ' . $batch->batch_code)
@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.batches.index') }}">Batches</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.batches.show', $batch->id) }}">{{ $batch->batch_code }}</a></li>
                    <li class="breadcrumb-item active">Candidates</li>
                </ol>
            </nav>
            <h2>Batch Candidates - {{ $batch->batch_code }}</h2>
            <p class="text-muted">
                {{ $batch->name }} | {{ $batch->trade->name ?? 'N/A' }} | {{ $batch->campus->name ?? 'N/A' }}
            </p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('admin.batches.show', $batch->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Batch
            </a>
        </div>
    </div>

    <!-- Batch Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Enrolled</h5>
                    <h3 class="mb-0">{{ $batch->enrollment_count }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Capacity</h5>
                    <h3 class="mb-0">{{ $batch->capacity }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Available Slots</h5>
                    <h3 class="mb-0">{{ $batch->available_slots }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Enrollment Progress</h5>
                    <h3 class="mb-0">{{ $batch->getEnrollmentProgressPercentage() }}%</h3>
                </div>
            </div>
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

    <!-- Search and Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.batches.candidates', $batch->id) }}" class="form-inline">
                <div class="form-group mb-2 mr-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, ID, CNIC..." value="{{ request('search') }}">
                </div>

                <div class="form-group mb-2 mr-3">
                    <select name="training_status" class="form-control">
                        <option value="">All Training Statuses</option>
                        <option value="enrolled" {{ request('training_status') == 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                        <option value="in_progress" {{ request('training_status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('training_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="dropped" {{ request('training_status') == 'dropped' ? 'selected' : '' }}>Dropped</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mb-2 mr-2">
                    <i class="fas fa-search"></i> Filter
                </button>

                @if(request()->hasAny(['search', 'training_status']))
                    <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="btn btn-secondary mb-2">
                        <i class="fas fa-times"></i> Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Candidates Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Candidates ({{ $candidates->total() }})</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>TheLeap ID</th>
                        <th>Name</th>
                        <th>CNIC</th>
                        <th>Trade</th>
                        <th>Training Status</th>
                        <th>Assigned Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($candidates as $candidate)
                        <tr>
                            <td><span class="badge badge-primary">{{ $candidate->btevta_id }}</span></td>
                            <td>{{ $candidate->name }}</td>
                            <td>{{ $candidate->cnic }}</td>
                            <td>{{ $candidate->trade->name ?? 'N/A' }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'enrolled' => 'info',
                                        'in_progress' => 'primary',
                                        'completed' => 'success',
                                        'dropped' => 'danger',
                                    ];
                                    $statusColor = $statusColors[$candidate->training_status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $candidate->training_status ?? 'N/A')) }}</span>
                            </td>
                            <td>{{ $candidate->batch_assigned_date ? $candidate->batch_assigned_date->format('d M Y') : 'N/A' }}</td>
                            <td>
                                @can('view', $candidate)
                                <a href="{{ route('admin.candidates.show', $candidate->id) }}" class="btn btn-sm btn-info" title="View Candidate">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No candidates found in this batch</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $candidates->links() }}
    </div>
</div>
@endsection
