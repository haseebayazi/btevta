@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    @if($candidate->photo_path)
                        <img src="{{ asset('storage/' . $candidate->photo_path) }}" 
                             alt="{{ $candidate->name }}" 
                             class="img-fluid rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded-circle mb-3 mx-auto" 
                             style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                    @endif
                    <h5>{{ $candidate->name }}</h5>
                    <p class="text-muted">BTEVTA ID: {{ $candidate->btevta_id }}</p>
                    <span class="badge bg-primary">{{ ucfirst($candidate->status) }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Name:</strong><br>
                            {{ $candidate->name }}
                        </div>
                        <div class="col-md-6">
                            <strong>Father Name:</strong><br>
                            {{ $candidate->father_name }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>CNIC:</strong><br>
                            {{ $candidate->cnic }}
                        </div>
                        <div class="col-md-6">
                            <strong>Date of Birth:</strong><br>
                            {{ $candidate->date_of_birth->format('d-m-Y') }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Gender:</strong><br>
                            {{ ucfirst($candidate->gender) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong><br>
                            {{ $candidate->phone }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Email:</strong><br>
                            {{ $candidate->email ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>District:</strong><br>
                            {{ $candidate->district }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Academic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Trade:</strong><br>
                            {{ $candidate->trade->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Campus:</strong><br>
                            {{ $candidate->campus->name ?? 'Not Assigned' }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Batch:</strong><br>
                            {{ $candidate->batch->name ?? 'Not Assigned' }}
                        </div>
                        <div class="col-md-6">
                            <strong>OEP:</strong><br>
                            {{ $candidate->oep->name ?? 'Not Assigned' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('candidates.edit', $candidate) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('candidates.timeline', $candidate) }}" class="btn btn-info">
                    <i class="fas fa-history"></i> Timeline
                </a>
                <a href="{{ route('candidates.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection