@extends('layouts.app')
@section('title', 'Batches Management')
@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Batches Management</h2>
        </div>
        <div class="col-md-4 text-right">
            @can('create', App\Models\Batch::class)
            <a href="{{ route('admin.batches.create') }}" class="btn btn-primary">+ Add New Batch</a>
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

    <!-- Search and Filter Section -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.batches.index') }}" class="form-inline">
                <div class="form-group mb-2 mr-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by batch code, name..." value="{{ request('search') }}">
                </div>

                <div class="form-group mb-2 mr-3">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-2 mr-3">
                    <select name="campus_id" class="form-control">
                        <option value="">All Campuses</option>
                        @foreach($campuses as $id => $name)
                            <option value="{{ $id }}" {{ request('campus_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-2 mr-3">
                    <select name="trade_id" class="form-control">
                        <option value="">All Trades</option>
                        @foreach($trades as $id => $name)
                            <option value="{{ $id }}" {{ request('trade_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mb-2 mr-2">
                    <i class="fas fa-search"></i> Filter
                </button>

                @if(request()->hasAny(['search', 'status', 'campus_id', 'trade_id', 'district']))
                    <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary mb-2">
                        <i class="fas fa-times"></i> Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

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
                                @php
                                    $statusColors = [
                                        \App\Models\Batch::STATUS_PLANNED => 'warning',
                                        \App\Models\Batch::STATUS_ACTIVE => 'success',
                                        \App\Models\Batch::STATUS_COMPLETED => 'secondary',
                                        \App\Models\Batch::STATUS_CANCELLED => 'danger',
                                    ];
                                    $batchStatuses = \App\Models\Batch::getStatuses();
                                    $statusColor = $statusColors[$batch->status] ?? 'secondary';
                                    $statusLabel = $batchStatuses[$batch->status] ?? ucfirst($batch->status);
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                            </td>
                            <td>{{ $batch->candidates_count ?? 0 }}</td>
                            <td>
                                @can('view', $batch)
                                <a href="{{ route('admin.batches.show', $batch->id) }}" class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="btn btn-sm btn-success" title="View Candidates">
                                    <i class="fas fa-users"></i>
                                </a>
                                @endcan
                                @can('update', $batch)
                                <a href="{{ route('admin.batches.edit', $batch->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('delete', $batch)
                                <form method="POST" action="{{ route('admin.batches.destroy', $batch->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this batch?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
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