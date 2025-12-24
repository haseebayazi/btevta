@extends('layouts.app')
@section('title', 'Create Batch')
@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.batches.index') }}">Batches</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Batch</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Create New Batch</h4>
                    <a href="{{ route('admin.batches.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.batches.store') }}">
                        @csrf

                        {{-- Basic Information Section --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="batch_code">Batch Code <span class="text-danger">*</span></label>
                                            <input type="text" id="batch_code" name="batch_code"
                                                   class="form-control @error('batch_code') is-invalid @enderror"
                                                   value="{{ old('batch_code') }}"
                                                   placeholder="e.g., BATCH-2025-001" required>
                                            <small class="form-text text-muted">Unique identifier for this batch</small>
                                            @error('batch_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Batch Name</label>
                                            <input type="text" id="name" name="name"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   value="{{ old('name') }}"
                                                   placeholder="e.g., Electrician Batch 2025">
                                            <small class="form-text text-muted">Optional descriptive name</small>
                                            @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="trade_id">Trade <span class="text-danger">*</span></label>
                                            <select id="trade_id" name="trade_id"
                                                    class="form-control @error('trade_id') is-invalid @enderror" required>
                                                <option value="">-- Select Trade --</option>
                                                @foreach($trades as $id => $name)
                                                    <option value="{{ $id }}" {{ old('trade_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('trade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="campus_id">Campus <span class="text-danger">*</span></label>
                                            <select id="campus_id" name="campus_id"
                                                    class="form-control @error('campus_id') is-invalid @enderror" required>
                                                <option value="">-- Select Campus --</option>
                                                @foreach($campuses as $id => $name)
                                                    <option value="{{ $id }}" {{ old('campus_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('campus_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Schedule Section --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Schedule & Capacity</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" id="start_date" name="start_date"
                                                   class="form-control @error('start_date') is-invalid @enderror"
                                                   value="{{ old('start_date') }}" required>
                                            @error('start_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="end_date">End Date</label>
                                            <input type="date" id="end_date" name="end_date"
                                                   class="form-control @error('end_date') is-invalid @enderror"
                                                   value="{{ old('end_date') }}">
                                            <small class="form-text text-muted">Leave empty if unknown</small>
                                            @error('end_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="capacity">Capacity <span class="text-danger">*</span></label>
                                            <input type="number" id="capacity" name="capacity"
                                                   class="form-control @error('capacity') is-invalid @enderror"
                                                   value="{{ old('capacity', 30) }}" min="1" max="500" required>
                                            <small class="form-text text-muted">Maximum candidates allowed</small>
                                            @error('capacity') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="status">Status <span class="text-danger">*</span></label>
                                            <select id="status" name="status"
                                                    class="form-control @error('status') is-invalid @enderror" required>
                                                <option value="planned" {{ old('status', 'planned') === 'planned' ? 'selected' : '' }}>Planned</option>
                                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="intake_period">Intake Period</label>
                                            <input type="text" id="intake_period" name="intake_period"
                                                   class="form-control @error('intake_period') is-invalid @enderror"
                                                   value="{{ old('intake_period') }}"
                                                   placeholder="e.g., January 2025">
                                            @error('intake_period') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="district">District</label>
                                            <input type="text" id="district" name="district"
                                                   class="form-control @error('district') is-invalid @enderror"
                                                   value="{{ old('district') }}"
                                                   placeholder="e.g., Lahore">
                                            @error('district') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Staff Assignment Section --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Staff Assignment</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="trainer_id">Trainer</label>
                                            <select id="trainer_id" name="trainer_id"
                                                    class="form-control @error('trainer_id') is-invalid @enderror">
                                                <option value="">-- Select Trainer (Optional) --</option>
                                                @foreach($users as $id => $name)
                                                    <option value="{{ $id }}" {{ old('trainer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('trainer_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="coordinator_id">Coordinator</label>
                                            <select id="coordinator_id" name="coordinator_id"
                                                    class="form-control @error('coordinator_id') is-invalid @enderror">
                                                <option value="">-- Select Coordinator (Optional) --</option>
                                                @foreach($users as $id => $name)
                                                    <option value="{{ $id }}" {{ old('coordinator_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('coordinator_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Additional Information Section --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i>Additional Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="specialization">Specialization</label>
                                            <input type="text" id="specialization" name="specialization"
                                                   class="form-control @error('specialization') is-invalid @enderror"
                                                   value="{{ old('specialization') }}"
                                                   placeholder="e.g., Industrial Electrician, Domestic Wiring">
                                            @error('specialization') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea id="description" name="description"
                                                      class="form-control @error('description') is-invalid @enderror"
                                                      rows="3"
                                                      placeholder="Enter any additional notes or description for this batch...">{{ old('description') }}</textarea>
                                            @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times mr-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save mr-1"></i> Create Batch
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
