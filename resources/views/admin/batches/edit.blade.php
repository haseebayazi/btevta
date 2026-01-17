@extends('layouts.app')
@section('title', 'Edit Batch')
@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.batches.index') }}" class="text-decoration-none">Batches</a></li>
            <li class="breadcrumb-item active">Edit Batch</li>
        </ol>
    </nav>

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-gray-800 font-weight-bold">Edit Batch: {{ $batch->batch_code }}</h2>
        <a href="{{ route('admin.batches.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>

    {{-- Error/Success Alerts --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    {{-- Main Form --}}
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.batches.update', $batch->id) }}">
                @csrf
                @method('PUT')

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
                                       value="{{ old('batch_code', $batch->batch_code) }}"
                                       placeholder="e.g., BATCH-2025-001" required>
                                <small class="text-muted">Unique identifier for this batch</small>
                                @error('batch_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label font-weight-bold">Batch Name</label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $batch->name) }}"
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
                                        <option value="{{ $id }}" {{ old('trade_id', $batch->trade_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('trade_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="campus_id" class="form-label font-weight-bold">Campus <span class="text-danger">*</span></label>
                                <select id="campus_id" name="campus_id" class="form-control @error('campus_id') is-invalid @enderror" required>
                                    <option value="">-- Select Campus --</option>
                                    @foreach($campuses as $id => $name)
                                        <option value="{{ $id }}" {{ old('campus_id', $batch->campus_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('campus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="oep_id" class="form-label font-weight-bold">OEP (Overseas Employment Promoter)</label>
                                <select id="oep_id" name="oep_id" class="form-control @error('oep_id') is-invalid @enderror">
                                    <option value="">-- Select OEP (Optional) --</option>
                                    @foreach($oeps as $id => $name)
                                        <option value="{{ $id }}" {{ old('oep_id', $batch->oep_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Optional: Select if batch is sponsored by an OEP</small>
                                @error('oep_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                       value="{{ old('start_date', $batch->start_date ? $batch->start_date->format('Y-m-d') : '') }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_date" class="form-label font-weight-bold">End Date</label>
                                <input type="date" id="end_date" name="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date', $batch->end_date ? $batch->end_date->format('Y-m-d') : '') }}">
                                <small class="text-muted">Optional</small>
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="capacity" class="form-label font-weight-bold">Capacity <span class="text-danger">*</span></label>
                                <input type="number" id="capacity" name="capacity"
                                       class="form-control @error('capacity') is-invalid @enderror"
                                       value="{{ old('capacity', $batch->capacity) }}" min="1" max="500" required>
                                @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label font-weight-bold">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                                    @foreach(\App\Models\Batch::getStatuses() as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', $batch->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="intake_period" class="form-label font-weight-bold">Intake Period</label>
                                <input type="text" id="intake_period" name="intake_period"
                                       class="form-control @error('intake_period') is-invalid @enderror"
                                       value="{{ old('intake_period', $batch->intake_period ?? '') }}" placeholder="e.g., January 2025">
                                @error('intake_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="district" class="form-label font-weight-bold">District</label>
                                <input type="text" id="district" name="district"
                                       class="form-control @error('district') is-invalid @enderror"
                                       value="{{ old('district', $batch->district ?? '') }}" placeholder="e.g., Lahore">
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
                                    @foreach($users ?? [] as $id => $name)
                                        <option value="{{ $id }}" {{ old('trainer_id', $batch->trainer_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('trainer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="coordinator_id" class="form-label font-weight-bold">Coordinator</label>
                                <select id="coordinator_id" name="coordinator_id" class="form-control @error('coordinator_id') is-invalid @enderror">
                                    <option value="">-- Select Coordinator --</option>
                                    @foreach($users ?? [] as $id => $name)
                                        <option value="{{ $id }}" {{ old('coordinator_id', $batch->coordinator_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
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
                                   value="{{ old('specialization', $batch->specialization ?? '') }}"
                                   placeholder="e.g., Industrial Electrician, Domestic Wiring">
                            @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-0">
                            <label for="description" class="form-label font-weight-bold">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Additional notes about this batch...">{{ old('description', $batch->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('admin.batches.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-save mr-1"></i> Update Batch
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Batch Stats --}}
            <div class="card shadow-sm border-left-primary mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar mr-2"></i>Batch Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h4 mb-0 text-primary">{{ $batch->candidates_count ?? $batch->candidates()->count() }}</div>
                            <small class="text-muted">Candidates</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 mb-0 text-success">{{ $batch->capacity }}</div>
                            <small class="text-muted">Capacity</small>
                        </div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        @php
                            $candidateCount = $batch->candidates_count ?? $batch->candidates()->count();
                            $percentage = $batch->capacity > 0 ? min(100, ($candidateCount / $batch->capacity) * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentage }}%"></div>
                    </div>
                    <small class="text-muted">{{ number_format($percentage, 0) }}% filled</small>
                </div>
            </div>

            {{-- Status Info --}}
            <div class="card shadow-sm border-left-info mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle mr-2"></i>Status Guide
                    </h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0 pl-3">
                        <li class="mb-2"><strong>Planned:</strong> Batch is scheduled but not started</li>
                        <li class="mb-2"><strong>Active:</strong> Training is in progress</li>
                        <li class="mb-2"><strong>Completed:</strong> Training has finished</li>
                        <li class="mb-2"><strong>Cancelled:</strong> Batch was cancelled</li>
                    </ul>
                </div>
            </div>

            {{-- Metadata --}}
            <div class="card shadow-sm border-left-secondary">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-clock mr-2"></i>Record Info
                    </h6>
                </div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Created:</strong> {{ $batch->created_at->format('M d, Y h:i A') }}</p>
                    <p class="mb-0"><strong>Last Updated:</strong> {{ $batch->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
