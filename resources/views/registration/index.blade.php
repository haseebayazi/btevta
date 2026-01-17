@extends('layouts.app')
@section('title', 'Registration Management')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Registration Management</h2>
            <p class="text-muted">Manage candidate registrations and documentation</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Registrations</h5>
                    <h2>{{ $candidates->total() }}</h2>
                </div>
            </div>
        </div>
    </div>

    @if($candidates->count())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Candidates Pending Registration</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>TheLeap ID</th>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Documents</th>
                            <th>Next of Kin</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidates as $candidate)
                            <tr>
                                <td><strong class="text-monospace">{{ $candidate->btevta_id }}</strong></td>
                                <td>{{ $candidate->name }}</td>
                                <td class="text-monospace">{{ $candidate->cnic ?? '-' }}</td>
                                <td>{{ $candidate->campus->name ?? 'N/A' }}</td>
                                <td>{{ $candidate->trade->name ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $docsCount = $candidate->documents->count();
                                    @endphp
                                    @if($docsCount > 0)
                                        <span class="badge badge-success">{{ $docsCount }} uploaded</span>
                                    @else
                                        <span class="badge badge-warning">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if($candidate->nextOfKin)
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Added</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Missing</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $candidate->status === 'registered' ? 'success' : 'warning' }}">
                                        {{ ucfirst($candidate->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('registration.show', $candidate->id) }}" class="btn btn-sm btn-primary" title="Manage Registration">
                                        <i class="fas fa-file-alt"></i> Manage
                                    </a>
                                    <a href="{{ route('candidates.show', $candidate->id) }}" class="btn btn-sm btn-info" title="View Profile">
                                        <i class="fas fa-user"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $candidates->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No candidates pending registration found.
        </div>
    @endif
</div>
@endsection
