@extends('layouts.app')
@section('title', 'Training Details')
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>{{ $candidate->name }} - Training Details</h2>
            <a href="{{ route('training.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Candidate Info</div>
                <div class="card-body">
                    <p><strong>BTEVTA ID:</strong> {{ $candidate->btevta_id }}</p>
                    <p><strong>Batch:</strong> {{ $candidate->batch?->batch_number ?? 'N/A' }}</p>
                    <p><strong>Trade:</strong> {{ $candidate->trade?->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Attendance</div>
                <div class="card-body">
                    <p><strong>Present:</strong> {{ $candidate->attendances()->where('status', 'present')->count() }}</p>
                    <p><strong>Absent:</strong> {{ $candidate->attendances()->where('status', 'absent')->count() }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
