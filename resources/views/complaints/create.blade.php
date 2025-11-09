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
                                    <select name="complaint_type" class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="candidate">Candidate Related</option>
                                        <option value="campus">Campus Related</option>
                                        <option value="oep">OEP Related</option>
                                        <option value="system">System/Process Related</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Priority <span class="text-danger">*</span></label>
                                    <select name="priority" class="form-control" required>
                                        <option value="">Select Priority</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Candidate (if applicable)</label>
                                    <select name="candidate_id" class="form-control">
                                        <option value="">Select Candidate</option>
                                        @foreach($candidates as $candidate)
                                            <option value="{{ $candidate->id }}">
                                                {{ $candidate->name }} - {{ $candidate->cnic ?? $candidate->passport_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Campus (if applicable)</label>
                                    <select name="campus_id" class="form-control">
                                        <option value="">Select Campus</option>
                                        @foreach($campuses as $campus)
                                            <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>OEP (if applicable)</label>
                                    <select name="oep_id" class="form-control">
                                        <option value="">Select OEP</option>
                                        @foreach($oeps as $oep)
                                            <option value="{{ $oep->id }}">{{ $oep->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="documentation">Documentation Issues</option>
                                <option value="training">Training Quality</option>
                                <option value="conduct">Misconduct/Behavior</option>
                                <option value="delay">Delays in Process</option>
                                <option value="financial">Financial Disputes</option>
                                <option value="facilities">Facilities/Infrastructure</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subject/Title <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" required
                                   placeholder="Brief description of the complaint">
                        </div>

                        <div class="form-group">
                            <label>Complaint Details <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="6" required
                                      placeholder="Provide detailed information about the complaint..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Supporting Documents/Evidence</label>
                            <input type="file" name="attachments[]" class="form-control" multiple>
                            <small class="form-text text-muted">You can attach multiple files (PDF, images)</small>
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
