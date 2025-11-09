@extends('layouts.app')
@section('title', 'Correspondence Details')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Correspondence Details</h2>
            <p class="text-muted">Reference: <strong class="text-monospace">{{ $correspondence->reference_number }}</strong></p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            @if($correspondence->requires_reply && !$correspondence->replied)
                <button class="btn btn-success" data-toggle="modal" data-target="#replyModal">
                    <i class="fas fa-reply"></i> Mark as Replied
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Main Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Correspondence Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong>
                            <span class="badge badge-{{ $correspondence->correspondence_type === 'incoming' ? 'info' : 'success' }} ml-2">
                                <i class="fas fa-{{ $correspondence->correspondence_type === 'incoming' ? 'inbox' : 'paper-plane' }}"></i>
                                {{ ucfirst($correspondence->correspondence_type) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong> {{ $correspondence->correspondence_date->format('F d, Y') }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>{{ $correspondence->correspondence_type === 'incoming' ? 'From' : 'To' }}:</strong>
                            {{ $correspondence->from_to ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Campus:</strong> {{ $correspondence->campus->name ?? 'Headquarters' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Subject:</strong>
                        <p class="mt-2">{{ $correspondence->subject }}</p>
                    </div>

                    @if($correspondence->description)
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="mt-2">{{ $correspondence->description }}</p>
                        </div>
                    @endif

                    @if($correspondence->attachments && count($correspondence->attachments) > 0)
                        <div class="mb-3">
                            <strong>Attachments:</strong>
                            <ul class="list-unstyled mt-2">
                                @foreach($correspondence->attachments as $attachment)
                                    <li>
                                        <a href="{{ Storage::url($attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file"></i> {{ basename($attachment) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> Created by {{ $correspondence->createdBy->name ?? 'System' }}
                            on {{ $correspondence->created_at->format('F d, Y H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Status Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Requires Reply:</strong>
                        @if($correspondence->requires_reply)
                            <span class="badge badge-warning">Yes</span>
                        @else
                            <span class="badge badge-secondary">No</span>
                        @endif
                    </div>

                    @if($correspondence->requires_reply)
                        <div class="mb-3">
                            <strong>Reply Status:</strong>
                            @if($correspondence->replied)
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Replied
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            @endif
                        </div>

                        @if($correspondence->reply_deadline)
                            <div class="mb-3">
                                <strong>Reply Deadline:</strong>
                                <br>{{ $correspondence->reply_deadline->format('F d, Y') }}
                                @if(!$correspondence->replied && $correspondence->reply_deadline < now())
                                    <span class="badge badge-danger ml-2">Overdue</span>
                                @endif
                            </div>
                        @endif

                        @if($correspondence->replied && $correspondence->replied_at)
                            <div class="mb-3">
                                <strong>Replied On:</strong>
                                <br>{{ $correspondence->replied_at->format('F d, Y H:i') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
@if($correspondence->requires_reply && !$correspondence->replied)
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('correspondence.mark-replied', $correspondence->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Mark as Replied</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reply Date</label>
                        <input type="date" name="replied_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Reply Notes</label>
                        <textarea name="reply_notes" class="form-control" rows="3" placeholder="Optional notes about the reply..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Replied</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
