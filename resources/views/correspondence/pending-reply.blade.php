@extends('layouts.app')
@section('title', 'Pending Replies')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Pending Replies</h2>
            <p class="text-muted">Correspondence items requiring response</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Correspondence
            </a>
            <a href="{{ route('correspondence.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Correspondence
            </a>
        </div>
    </div>

    @if($pendingReplies->count())
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>{{ $pendingReplies->count() }}</strong> correspondence items pending reply.
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-reply"></i> Pending Reply List</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Reference #</th>
                            <th>Date Received</th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Campus</th>
                            <th>Deadline</th>
                            <th>Days Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingReplies as $corr)
                            @php
                                $daysRemaining = $corr->reply_deadline ? now()->diffInDays($corr->reply_deadline, false) : null;
                                $isOverdue = $daysRemaining !== null && $daysRemaining < 0;
                            @endphp
                            <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                                <td><strong class="text-monospace">{{ $corr->reference_number }}</strong></td>
                                <td>{{ $corr->correspondence_date->format('Y-m-d') }}</td>
                                <td>{{ $corr->from_to ?? 'N/A' }}</td>
                                <td>{{ Str::limit($corr->subject, 40) }}</td>
                                <td>{{ $corr->campus->name ?? 'HQ' }}</td>
                                <td>
                                    @if($corr->reply_deadline)
                                        {{ $corr->reply_deadline->format('Y-m-d') }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($daysRemaining !== null)
                                        @if($isOverdue)
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-circle"></i> {{ abs($daysRemaining) }} days overdue
                                            </span>
                                        @elseif($daysRemaining <= 3)
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> {{ $daysRemaining }} days
                                            </span>
                                        @else
                                            <span class="badge badge-info">{{ $daysRemaining }} days</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('correspondence.show', $corr->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form action="{{ route('correspondence.mark-replied', $corr->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this correspondence as replied?')">
                                            <i class="fas fa-check"></i> Mark Replied
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> No pending replies! All correspondence has been responded to.
        </div>
    @endif
</div>
@endsection
