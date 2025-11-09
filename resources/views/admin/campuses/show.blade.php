@extends('layouts.app')
@section('title', 'Campus Details')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <h2>{{ $campus->name }}</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('campuses.edit', $campus->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('campuses.index') }}" class="btn btn-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Campus Information</div>
                <div class="card-body">
                    <p><strong>Location:</strong> {{ $campus->district }}, {{ $campus->province }}</p>
                    <p><strong>Address:</strong> {{ $campus->address ?? '-' }}</p>
                    <p><strong>Contact Person:</strong> {{ $campus->contact_person }}</p>
                    <p><strong>Phone:</strong> {{ $campus->phone }}</p>
                    <p><strong>Email:</strong> {{ $campus->email }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">Statistics</div>
                <div class="card-body">
                    <h5>Total Candidates: <span class="badge badge-primary">{{ $campus->candidates->count() }}</span></h5>
                    <h5>Active Batches: <span class="badge badge-success">{{ $campus->batches->count() }}</span></h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection