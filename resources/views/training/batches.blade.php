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
                    <td><span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span></td>
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
