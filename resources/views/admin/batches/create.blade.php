@extends('layouts.app')
@section('title', 'Create Batch')
@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.batches.index') }}" class="text-decoration-none">Batches</a></li>
            <li class="breadcrumb-item active">Create New Batch</li>
        </ol>
    </nav>

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-gray-800 font-weight-bold">Create New Batch</h2>
        <a href="{{ route('admin.batches.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>

    {{-- Error Alert --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    {{-- Main Form --}}
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.batches.store') }}">
                @csrf

                {{-- Basic Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle mr-2"></i>Basic Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="batch_code" class="form-label font-weight-bold">Batch Code <span class="text-danger">*</span></label>
                                <input type="text" id="batch_code" name="batch_code"
                                       class="form-control @error('batch_code') is-invalid @enderror"
                                       value="{{ old('batch_code') }}"
                                       placeholder="e.g., BATCH-2025-001" required>
                                <small class="text-muted">Unique identifier for this batch</small>
                                @error('batch_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label font-weight-bold">Batch Name</label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="e.g., Electrician Batch Jan 2025">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="trade_id" class="form-label font-weight-bold">Trade <span class="text-danger">*</span></label>
                                <select id="trade_id" name="trade_id" class="form-control @error('trade_id') is-invalid @enderror" required>
                                    <option value="">-- Select Trade --</option>
                                    @foreach($trades as $id => $name)
                                        <option value="{{ $id }}" {{ old('trade_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('trade_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="campus_id" class="form-label font-weight-bold">Campus <span class="text-danger">*</span></label>
                                <select id="campus_id" name="campus_id" class="form-control @error('campus_id') is-invalid @enderror" required>
                                    <option value="">-- Select Campus --</option>
                                    @foreach($campuses as $id => $name)
                                        <option value="{{ $id }}" {{ old('campus_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('campus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule & Capacity --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calendar-alt mr-2"></i>Schedule & Capacity
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="start_date" class="form-label font-weight-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" id="start_date" name="start_date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date') }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_date" class="form-label font-weight-bold">End Date</label>
                                <input type="date" id="end_date" name="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date') }}">
                                <small class="text-muted">Optional</small>
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="capacity" class="form-label font-weight-bold">Capacity <span class="text-danger">*</span></label>
                                <input type="number" id="capacity" name="capacity"
                                       class="form-control @error('capacity') is-invalid @enderror"
                                       value="{{ old('capacity', 30) }}" min="1" max="500" required>
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label font-weight-bold">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                                    @foreach(\App\Models\Batch::getStatuses() as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', \App\Models\Batch::STATUS_PLANNED) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="intake_period" class="form-label font-weight-bold">Intake Period</label>
                                <input type="text" id="intake_period" name="intake_period"
                                       class="form-control @error('intake_period') is-invalid @enderror"
                                       value="{{ old('intake_period') }}" placeholder="e.g., January 2025">
                                @error('intake_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="district" class="form-label font-weight-bold">District</label>
                                <input type="text" id="district" name="district"
                                       class="form-control @error('district') is-invalid @enderror"
                                       value="{{ old('district') }}" placeholder="e.g., Lahore">
                                @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Staff Assignment --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user-tie mr-2"></i>Staff Assignment (Optional)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="trainer_id" class="form-label font-weight-bold">Trainer</label>
                                <select id="trainer_id" name="trainer_id" class="form-control @error('trainer_id') is-invalid @enderror">
                                    <option value="">-- Select Trainer --</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('trainer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('trainer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="coordinator_id" class="form-label font-weight-bold">Coordinator</label>
                                <select id="coordinator_id" name="coordinator_id" class="form-control @error('coordinator_id') is-invalid @enderror">
                                    <option value="">-- Select Coordinator --</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('coordinator_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('coordinator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-file-alt mr-2"></i>Additional Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="specialization" class="form-label font-weight-bold">Specialization</label>
                            <input type="text" id="specialization" name="specialization"
                                   class="form-control @error('specialization') is-invalid @enderror"
                                   value="{{ old('specialization') }}"
                                   placeholder="e.g., Industrial Electrician, Domestic Wiring">
                            @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-0">
                            <label for="description" class="form-label font-weight-bold">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Additional notes about this batch...">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Create Batch
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar Help --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-left-info mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-lightbulb mr-2"></i>Quick Tips
                    </h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0 pl-3">
                        <li class="mb-2"><strong>Batch Code:</strong> Use a unique code like BATCH-2025-001</li>
                        <li class="mb-2"><strong>Status:</strong> Start with "Planned" and change to "Active" when training begins</li>
                        <li class="mb-2"><strong>Capacity:</strong> Maximum number of candidates for this batch</li>
                        <li class="mb-2"><strong>Staff:</strong> Trainer and coordinator can be assigned later</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-left-warning">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-info-circle mr-2"></i>Required Fields
                    </h6>
                </div>
                <div class="card-body small">
                    <p class="mb-2">Fields marked with <span class="text-danger">*</span> are required:</p>
                    <ul class="mb-0 pl-3">
                        <li>Batch Code</li>
                        <li>Trade</li>
                        <li>Campus</li>
                        <li>Start Date</li>
                        <li>Capacity</li>
                        <li>Status</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
