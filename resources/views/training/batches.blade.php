@extends('layouts.app')
@section('title', 'Training Batches')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Training Batches</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Batch Number</th>
                    <th>Trade</th>
                    <th>Campus</th>
                    <th>Status</th>
                    <th>Candidates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $batch)
                <tr>
                    <td>{{ $batch->batch_number }}</td>
                    <td>{{ $batch->trade?->name ?? 'N/A' }}</td>
                    <td>{{ $batch->campus?->name ?? 'N/A' }}</td>
                    <td><span class="badge badge-{{ $batch->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($batch->status) }}</span></td>
                    <td>{{ $batch->candidates_count }}</td>
                    <td>
                        <a href="{{ route('reports.batch-summary', $batch) }}" class="btn btn-sm btn-info">Report</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
