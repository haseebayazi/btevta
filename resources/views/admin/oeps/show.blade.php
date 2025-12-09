@extends('layouts.app')
@section('title', 'OEP Details')
@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $oep->name }}</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('admin.oeps.edit', $oep->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('admin.oeps.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">OEP Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>OEP Name:</strong>
                            <p>{{ $oep->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>OEP Code:</strong>
                            <p><span class="badge badge-info">{{ $oep->code }}</span></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Country:</strong>
                            <p>{{ $oep->country }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Contact Person:</strong>
                            <p>{{ $oep->contact_person ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Phone:</strong>
                            <p>{{ $oep->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong>
                            <p>{{ $oep->email ?? 'N/A' }}</p>
                        </div>
                    </div>

                    @if($oep->address)
                        <div class="mb-3">
                            <strong>Address:</strong>
                            <p>{{ $oep->address }}</p>
                        </div>
                    @endif

                    @if($oep->description)
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p>{{ $oep->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if(isset($oep->candidates) && $oep->candidates->count() > 0)
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Associated Candidates</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oep->candidates as $candidate)
                                    <tr>
                                        <td>{{ $candidate->name }}</td>
                                        <td>{{ $candidate->email }}</td>
                                        <td>{{ $candidate->phone ?? 'N/A' }}</td>
                                        <td><span class="badge badge-secondary">{{ $candidate->status ?? 'Active' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">Statistics</div>
                <div class="card-body">
                    <h5>Total Candidates: <span class="badge badge-primary">{{ $oep->candidates->count() ?? 0 }}</span></h5>
                    <h5 class="mt-3">Created: <small class="text-muted">{{ $oep->created_at->format('d M Y') }}</small></h5>
                    <h5>Updated: <small class="text-muted">{{ $oep->updated_at->format('d M Y') }}</small></h5>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-danger text-white">Actions</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.oeps.destroy', $oep->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to delete this OEP?')">
                            <i class="fas fa-trash"></i> Delete OEP
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection