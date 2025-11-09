@extends('layouts.app')
@section('title', 'Edit Screening')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Edit Screening Record</h1>
        <p class="text-gray-600 mt-2">Update screening details for {{ $candidate->name }}</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Candidate Info Card -->
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Candidate Information</h3>
            <div class="space-y-2 text-sm">
                <p><strong>BTEVTA ID:</strong> {{ $candidate->btevta_id }}</p>
                <p><strong>Name:</strong> {{ $candidate->name }}</p>
                <p><strong>CNIC:</strong> {{ $candidate->cnic }}</p>
                <p><strong>Phone:</strong> {{ $candidate->phone }}</p>
                <p><strong>Status:</strong>
                    <span class="badge badge-{{ $candidate->status == 'registered' ? 'success' : 'warning' }}">
                        {{ ucfirst($candidate->status) }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Screening Form -->
        <div class="md:col-span-2">
            <div class="card">
                <form action="{{ route('screening.update', $candidate->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="screening_date" class="required">Screening Date</label>
                            <input type="date"
                                   id="screening_date"
                                   name="screening_date"
                                   class="form-control @error('screening_date') is-invalid @enderror"
                                   value="{{ old('screening_date', $screening->screening_date ? $screening->screening_date->format('Y-m-d') : '') }}"
                                   required>
                            @error('screening_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="call_duration" class="required">Call Duration (minutes)</label>
                            <input type="number"
                                   id="call_duration"
                                   name="call_duration"
                                   class="form-control @error('call_duration') is-invalid @enderror"
                                   value="{{ old('call_duration', $screening->call_duration) }}"
                                   min="1"
                                   required>
                            @error('call_duration')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label for="call_notes">Call Notes</label>
                            <textarea id="call_notes"
                                      name="call_notes"
                                      class="form-control @error('call_notes') is-invalid @enderror"
                                      rows="4">{{ old('call_notes', $screening->call_notes) }}</textarea>
                            @error('call_notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="screening_outcome" class="required">Screening Outcome</label>
                            <select id="screening_outcome"
                                    name="screening_outcome"
                                    class="form-control @error('screening_outcome') is-invalid @enderror"
                                    required>
                                <option value="">Select Outcome</option>
                                <option value="pass" {{ old('screening_outcome', $screening->screening_outcome) == 'pass' ? 'selected' : '' }}>Pass</option>
                                <option value="fail" {{ old('screening_outcome', $screening->screening_outcome) == 'fail' ? 'selected' : '' }}>Fail</option>
                                <option value="pending" {{ old('screening_outcome', $screening->screening_outcome) == 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                            @error('screening_outcome')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label for="remarks">Remarks</label>
                            <textarea id="remarks"
                                      name="remarks"
                                      class="form-control @error('remarks') is-invalid @enderror"
                                      rows="3">{{ old('remarks', $screening->remarks) }}</textarea>
                            @error('remarks')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Screening
                        </button>
                        <a href="{{ route('screening.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
