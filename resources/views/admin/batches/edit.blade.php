@extends('layouts.app')
@section('title', 'Edit Batch')
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2>Edit Batch</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('batches.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST" action="{{ route('batches.update', $batch->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Batch Code <span class="text-danger">*</span></label>
                    <input type="text" name="batch_code" class="form-control @error('batch_code') is-invalid @enderror" 
                           value="{{ old('batch_code', $batch->batch_code) }}" placeholder="e.g., BATCH-2025-001" required>
                    @error('batch_code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Trade <span class="text-danger">*</span></label>
                    <select name="trade_id" class="form-control @error('trade_id') is-invalid @enderror" required>
                        <option value="">-- Select Trade --</option>
                        @foreach($trades as $id => $name)
                            <option value="{{ $id }}" {{ old('trade_id', $batch->trade_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('trade_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Campus <span class="text-danger">*</span></label>
                    <select name="campus_id" class="form-control @error('campus_id') is-invalid @enderror" required>
                        <option value="">-- Select Campus --</option>
                        @foreach($campuses as $id => $name)
                            <option value="{{ $id }}" {{ old('campus_id', $batch->campus_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('campus_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                           value="{{ old('start_date', $batch->start_date->format('Y-m-d')) }}" required>
                    @error('start_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                           value="{{ old('end_date', $batch->end_date ? $batch->end_date->format('Y-m-d') : '') }}">
                    @error('end_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Capacity <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror" 
                           value="{{ old('capacity', $batch->capacity) }}" min="1" required>
                    @error('capacity') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                        <option value="pending" {{ old('status', $batch->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ old('status', $batch->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status', $batch->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $batch->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $batch->name) }}">
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                              rows="3">{{ old('description', $batch->description) }}</textarea>
                    @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success">Update Batch</button>
                    <a href="{{ route('batches.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection