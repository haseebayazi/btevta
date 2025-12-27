@extends('layouts.app')
@section('title', 'File Complaint')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>File New Complaint</h2>
            <p class="text-muted">Register a formal complaint</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Complaints
            </a>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Complaint Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Complaint Type <span class="text-danger">*</span></label>
                                    <select name="complaint_type" class="form-control @error('complaint_type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="candidate" {{ old('complaint_type') == 'candidate' ? 'selected' : '' }}>Candidate Related</option>
                                        <option value="campus" {{ old('complaint_type') == 'campus' ? 'selected' : '' }}>Campus Related</option>
                                        <option value="oep" {{ old('complaint_type') == 'oep' ? 'selected' : '' }}>OEP Related</option>
                                        <option value="system" {{ old('complaint_type') == 'system' ? 'selected' : '' }}>System/Process Related</option>
                                    </select>
                                    @error('complaint_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Priority <span class="text-danger">*</span></label>
                                    <select name="priority" class="form-control @error('priority') is-invalid @enderror" required>
                                        <option value="">Select Priority</option>
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                                    @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Candidate (if applicable)</label>
                                    <select name="candidate_id" class="form-control @error('candidate_id') is-invalid @enderror">
                                        <option value="">Select Candidate</option>
                                        @foreach($candidates ?? [] as $candidate)
                                            <option value="{{ $candidate->id }}" {{ old('candidate_id') == $candidate->id ? 'selected' : '' }}>
                                                {{ $candidate->name }} - {{ $candidate->cnic ?? $candidate->passport_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('candidate_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Campus (if applicable)</label>
                                    <select name="campus_id" class="form-control @error('campus_id') is-invalid @enderror">
                                        <option value="">Select Campus</option>
                                        @foreach($campuses ?? [] as $campus)
                                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('campus_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>OEP (if applicable)</label>
                                    <select name="oep_id" class="form-control @error('oep_id') is-invalid @enderror">
                                        <option value="">Select OEP</option>
                                        @foreach($oeps ?? [] as $oep)
                                            <option value="{{ $oep->id }}" {{ old('oep_id') == $oep->id ? 'selected' : '' }}>{{ $oep->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('oep_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                <option value="documentation" {{ old('category') == 'documentation' ? 'selected' : '' }}>Documentation Issues</option>
                                <option value="training" {{ old('category') == 'training' ? 'selected' : '' }}>Training Quality</option>
                                <option value="conduct" {{ old('category') == 'conduct' ? 'selected' : '' }}>Misconduct/Behavior</option>
                                <option value="delay" {{ old('category') == 'delay' ? 'selected' : '' }}>Delays in Process</option>
                                <option value="financial" {{ old('category') == 'financial' ? 'selected' : '' }}>Financial Disputes</option>
                                <option value="facilities" {{ old('category') == 'facilities' ? 'selected' : '' }}>Facilities/Infrastructure</option>
                                <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Subject/Title <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" required
                                   value="{{ old('subject') }}"
                                   placeholder="Brief description of the complaint">
                            @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Complaint Details <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="6" required
                                      placeholder="Provide detailed information about the complaint...">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Supporting Documents/Evidence</label>
                            <input type="file" name="attachments[]" class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror" multiple>
                            <small class="form-text text-muted">You can attach multiple files (PDF, images)</small>
                            @error('attachments')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('attachments.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-paper-plane"></i> Submit Complaint
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
