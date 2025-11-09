@extends('layouts.app')
@section('title', 'Correspondence Management')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Correspondence Management</h2>
            <p class="text-muted">Manage all incoming and outgoing correspondence</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('correspondence.pending-reply') }}" class="btn btn-warning">
                <i class="fas fa-clock"></i> Pending Replies
            </a>
            <a href="{{ route('correspondence.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Correspondence
            </a>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Search by reference or subject..." value="{{ request('search') }}" style="width: 300px;">
                <select name="type" class="form-control mr-2">
                    <option value="">All Types</option>
                    <option value="incoming" {{ request('type') === 'incoming' ? 'selected' : '' }}>Incoming</option>
                    <option value="outgoing" {{ request('type') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                @if(request('search') || request('type'))
                    <a href="{{ route('correspondence.index') }}" class="btn btn-secondary ml-2">Clear</a>
                @endif
            </form>
        </div>
    </div>

    @if($correspondences->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Correspondence Records</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Reference #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From/To</th>
                            <th>Subject</th>
                            <th>Campus</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($correspondences as $corr)
                            <tr>
                                <td><strong class="text-monospace">{{ $corr->reference_number }}</strong></td>
                                <td>{{ $corr->correspondence_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge badge-{{ $corr->correspondence_type === 'incoming' ? 'info' : 'success' }}">
                                        <i class="fas fa-{{ $corr->correspondence_type === 'incoming' ? 'inbox' : 'paper-plane' }}"></i>
                                        {{ ucfirst($corr->correspondence_type) }}
                                    </span>
                                </td>
                                <td>{{ $corr->from_to ?? 'N/A' }}</td>
                                <td>{{ Str::limit($corr->subject, 50) }}</td>
                                <td>{{ $corr->campus->name ?? 'HQ' }}</td>
                                <td>
                                    @if($corr->requires_reply && !$corr->replied)
                                        <span class="badge badge-warning">
                                            <i class="fas fa-reply"></i> Reply Pending
                                        </span>
                                    @elseif($corr->replied)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Replied
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">No Reply Required</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('correspondence.show', $corr->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $correspondences->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No correspondence records found.
        </div>
    @endif
</div>
@endsection
