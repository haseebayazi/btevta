@extends('layouts.app')
@section('title', 'Log Screening Call')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Log Screening Call</h2>
            <p class="text-muted">Record a new candidate screening call</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('screening.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-phone"></i> Screening Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('screening.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="candidate_id">Candidate <span class="text-danger">*</span></label>
                            <select name="candidate_id" id="candidate_id" class="form-control" required>
                                <option value="">Select Candidate</option>
                                @foreach($candidates as $candidate)
                                    <option value="{{ $candidate->id }}" {{ request('candidate_id') == $candidate->id ? 'selected' : '' }}>
                                        {{ $candidate->name }} ({{ $candidate->btevta_id }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select the candidate being screened</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="screening_date">Screening Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="screening_date" id="screening_date"
                                           class="form-control" value="{{ old('screening_date', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="call_duration">Call Duration (minutes) <span class="text-danger">*</span></label>
                                    <input type="number" name="call_duration" id="call_duration"
                                           class="form-control" min="1" value="{{ old('call_duration') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="screening_outcome">Screening Outcome <span class="text-danger">*</span></label>
                            <select name="screening_outcome" id="screening_outcome" class="form-control" required>
                                <option value="">Select Outcome</option>
                                <option value="pending" {{ old('screening_outcome') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="pass" {{ old('screening_outcome') === 'pass' ? 'selected' : '' }}>Pass</option>
                                <option value="fail" {{ old('screening_outcome') === 'fail' ? 'selected' : '' }}>Fail</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="call_notes">Call Notes</label>
                            <textarea name="call_notes" id="call_notes" class="form-control" rows="4"
                                      placeholder="Enter notes from the screening call...">{{ old('call_notes') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="3"
                                      placeholder="Additional remarks or observations...">{{ old('remarks') }}</textarea>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('screening.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Screening Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
