@extends('layouts.app')
@section('title', 'New Correspondence')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>New Correspondence</h2>
            <p class="text-muted">Register new incoming or outgoing correspondence</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Correspondence Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('correspondence.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reference Number <span class="text-danger">*</span></label>
                                    <input type="text" name="reference_number" class="form-control" required
                                           placeholder="e.g., CORR-2025-001" value="{{ old('reference_number') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date <span class="text-danger">*</span></label>
                                    <input type="date" name="correspondence_date" class="form-control" required
                                           value="{{ old('correspondence_date', date('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Type <span class="text-danger">*</span></label>
                                    <select name="correspondence_type" class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="incoming" {{ old('correspondence_type') === 'incoming' ? 'selected' : '' }}>Incoming</option>
                                        <option value="outgoing" {{ old('correspondence_type') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>From/To <span class="text-danger">*</span></label>
                                    <input type="text" name="from_to" class="form-control" required
                                           placeholder="Organization/Department name" value="{{ old('from_to') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Campus (if applicable)</label>
                                    <select name="campus_id" class="form-control">
                                        <option value="">Headquarters</option>
                                        @if(isset($campuses))
                                            @foreach($campuses as $campus)
                                                <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                                    {{ $campus->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" required
                                   placeholder="Brief subject of correspondence" value="{{ old('subject') }}">
                        </div>

                        <div class="form-group">
                            <label>Description/Content</label>
                            <textarea name="description" class="form-control" rows="5"
                                      placeholder="Detailed description of the correspondence...">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="requires_reply" name="requires_reply" value="1" {{ old('requires_reply') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="requires_reply">
                                            Requires Reply
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reply Deadline (if applicable)</label>
                                    <input type="date" name="reply_deadline" class="form-control" value="{{ old('reply_deadline') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Attach Files</label>
                            <input type="file" name="attachments[]" class="form-control-file" multiple>
                            <small class="form-text text-muted">You can attach multiple files (PDF, images, documents)</small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Correspondence
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
