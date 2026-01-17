@extends('layouts.app')
@section('title', 'Batch Details')
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2>{{ $batch->batch_code }}</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('admin.batches.edit', $batch->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Batch Information</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Batch Code:</strong>
                    <p><span class="badge badge-info">{{ $batch->batch_code }}</span></p>
                </div>
                <div class="col-md-6">
                    <strong>Status:</strong>
                    <p>
                        @if($batch->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($batch->status === 'completed')
                            <span class="badge badge-secondary">Completed</span>
                        @elseif($batch->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">{{ ucfirst($batch->status) }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Trade:</strong>
                    <p>{{ $batch->trade->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <strong>Campus:</strong>
                    <p>{{ $batch->campus->name ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Start Date:</strong>
                    <p>{{ $batch->start_date->format('d M Y') }}</p>
                </div>
                <div class="col-md-6">
                    <strong>End Date:</strong>
                    <p>{{ $batch->end_date ? $batch->end_date->format('d M Y') : 'N/A' }}</p>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Capacity:</strong>
                    <p>{{ $batch->capacity }} candidates</p>
                </div>
                <div class="col-md-6">
                    <strong>Trainer:</strong>
                    <p>{{ $batch->trainer_name ?? ($batch->trainer ? $batch->trainer->name : 'N/A') }}</p>
                </div>
            </div>

            @if($batch->description)
                <div class="mb-3">
                    <strong>Description:</strong>
                    <p>{{ $batch->description }}</p>
                </div>
            @endif
        </div>
    </div>

    @if($batch->candidates && $batch->candidates->count() > 0)
        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Enrolled Candidates ({{ $batch->candidates->count() }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Name</th>
                            <th>TheLeap ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch->candidates as $candidate)
                            <tr>
                                <td>{{ $candidate->name }}</td>
                                <td>{{ $candidate->btevta_id }}</td>
                                <td><span class="badge badge-secondary">{{ $candidate->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card mt-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Total Candidates:</strong>
                    <p>{{ $batch->candidates->count() ?? 0 }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Capacity:</strong>
                    <p>{{ $batch->capacity }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Available Seats:</strong>
                    <p>{{ $batch->capacity - ($batch->candidates->count() ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Danger Zone</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.batches.destroy', $batch->id) }}" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this batch? This cannot be undone.')">
                    Delete Batch
                </button>
            </form>
        </div>
    </div>
</div>
@endsection