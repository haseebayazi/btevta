@extends('layouts.app')
@section('title', 'Trade Details')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <h2>{{ $trade->name }}</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('admin.trades.edit', $trade->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('admin.trades.index') }}" class="btn btn-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Trade Information</div>
                <div class="card-body">
                    <p><strong>Code:</strong> <span class="badge badge-info">{{ $trade->code }}</span></p>
                    <p><strong>Category:</strong> {{ $trade->category }}</p>
                    <p><strong>Duration:</strong> {{ $trade->duration_weeks }} weeks</p>
                    <p><strong>Description:</strong> {{ $trade->description ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">Statistics</div>
                <div class="card-body">
                    <h5>Total Candidates: <span class="badge badge-primary">{{ $trade->candidates->count() }}</span></h5>
                    <h5>Active Batches: <span class="badge badge-success">{{ $trade->batches->count() }}</span></h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection