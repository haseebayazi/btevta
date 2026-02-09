@extends('layouts.app')

@section('title', 'Training Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Training Management</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('training.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Training
            </a>
            <a href="{{ route('admin.batches.index') }}" class="btn btn-info">
                <i class="fas fa-list"></i> Batches
            </a>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Module 4: Dual-Status Dashboard Notice --}}
    @if($batches ?? false)
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Dual-Status Training Dashboard (New)</h5>
        </div>
        <div class="card-body">
            <p class="mb-3">Track Technical and Soft Skills training separately for enhanced progress monitoring.</p>
            <div class="row">
                @foreach($batches as $batch)
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border">
                        <div class="card-body">
                            <h6 class="card-title">{{ $batch->name }}</h6>
                            <p class="card-text text-muted small mb-2">{{ $batch->batch_code }}</p>
                            <a href="{{ route('training.dual-status-dashboard', $batch) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-chart-bar mr-1"></i> View Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Candidates in Training</h5>
        </div>
        <div class="card-body">
            @if($candidates->count())
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>TheLeap ID</th>
                                <th>Name</th>
                                <th>Trade</th>
                                <th>Campus</th>
                                <th>Batch</th>
                                <th>Attendance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $candidate)
                                <tr>
                                    <td>{{ $candidate->btevta_id }}</td>
                                    <td>{{ $candidate->name }}</td>
                                    <td>{{ $candidate->trade?->name ?? 'N/A' }}</td>
                                    <td>{{ $candidate->campus?->name ?? 'N/A' }}</td>
                                    <td>{{ $candidate->batch?->batch_number ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $attendance = $candidate->attendances()->where('status', 'present')->count();
                                            $total = $candidate->attendances()->count();
                                        @endphp
                                        {{ $total > 0 ? round(($attendance / $total) * 100) : 0 }}%
                                    </td>
                                    <td>
                                        <a href="{{ route('training.show', $candidate) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('training.edit', $candidate) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('training.destroy', $candidate) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Remove from training?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $candidates->links() }}
            @else
                <div class="alert alert-info">
                    No candidates in training. <a href="{{ route('training.create') }}">Start now</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection