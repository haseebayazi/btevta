@extends('layouts.app')
@section('title', 'Batches Management')
@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Batches Management</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('batches.create') }}" class="btn btn-primary">+ Add New Batch</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Batch Code</th>
                        <th>Trade</th>
                        <th>Campus</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Candidates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->id }}</td>
                            <td><span class="badge badge-info">{{ $batch->batch_code }}</span></td>
                            <td>{{ $batch->trade->name ?? 'N/A' }}</td>
                            <td>{{ $batch->campus->name ?? 'N/A' }}</td>
                            <td>{{ $batch->start_date->format('d M Y') }}</td>
                            <td>
                                @if($batch->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($batch->status === 'completed')
                                    <span class="badge badge-secondary">Completed</span>
                                @elseif($batch->status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-danger">{{ ucfirst($batch->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $batch->candidates_count ?? 0 }}</td>
                            <td>
                                <a href="{{ route('batches.show', $batch->id) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('batches.edit', $batch->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="{{ route('batches.destroy', $batch->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No batches found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $batches->links() }}
    </div>
</div>
@endsection