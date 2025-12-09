@extends('layouts.app')
@section('title', 'Add Candidates to Training')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Add Candidates to Training</h2>
            <p class="text-muted">Enroll registered candidates into a training batch</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('training.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Training
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Training Enrollment</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('training.store') }}">
                        @csrf

                        <div class="form-group">
                            <label for="batch_id">Select Batch <span class="text-danger">*</span></label>
                            <select name="batch_id" id="batch_id" class="form-control @error('batch_id') is-invalid @enderror" required>
                                <option value="">-- Select Training Batch --</option>
                                @forelse($batches ?? [] as $batch)
                                    <option value="{{ $batch->id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>
                                        {{ $batch->batch_number ?? $batch->name }}
                                        @if($batch->trade)
                                            ({{ $batch->trade->name }})
                                        @endif
                                        @if($batch->start_date)
                                            - Starts: {{ $batch->start_date->format('M d, Y') }}
                                        @endif
                                    </option>
                                @empty
                                    <option value="" disabled>No active batches available</option>
                                @endforelse
                            </select>
                            @error('batch_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                Choose the batch where candidates will be enrolled for training.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Select Candidates <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-2">Hold Ctrl/Cmd to select multiple candidates</p>
                            <select name="candidate_ids[]" id="candidate_ids"
                                    class="form-control @error('candidate_ids') is-invalid @enderror"
                                    multiple required size="12"
                                    style="min-height: 300px;">
                                @forelse($candidates ?? [] as $candidate)
                                    <option value="{{ $candidate->id }}" {{ in_array($candidate->id, old('candidate_ids', [])) ? 'selected' : '' }}>
                                        {{ $candidate->name }}
                                        ({{ $candidate->btevta_id ?? $candidate->application_id ?? 'N/A' }})
                                        @if($candidate->trade)
                                            - {{ $candidate->trade->name }}
                                        @endif
                                    </option>
                                @empty
                                    <option value="" disabled>No candidates available for training enrollment</option>
                                @endforelse
                            </select>
                            @error('candidate_ids')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Selected candidates will be enrolled in the chosen batch and their status will be updated to "Training".
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('training.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Add to Training
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
