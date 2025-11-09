@extends('layouts.app')
@section('title', 'Registration Details -' . $candidate->name)
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Registration Management</h2>
            <h4 class="text-muted">{{ $candidate->name }} ({{ $candidate->btevta_id }})</h4>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('registration.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('candidates.show', $candidate->id) }}" class="btn btn-info">
                <i class="fas fa-user"></i> View Profile
            </a>
        </div>
    </div>

    <!-- Candidate Basic Info -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user"></i> Candidate Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>BTEVTA ID:</strong> <span class="text-monospace">{{ $candidate->btevta_id }}</span>
                </div>
                <div class="col-md-4">
                    <strong>CNIC:</strong> <span class="text-monospace">{{ $candidate->cnic ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <strong>Status:</strong>
                    <span class="badge badge-{{ $candidate->status === 'registered' ? 'success' : 'warning' }}">
                        {{ ucfirst($candidate->status) }}
                    </span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <strong>Campus:</strong> {{ $candidate->campus->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Trade:</strong> {{ $candidate->trade->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Phone:</strong> {{ $candidate->phone ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Registration Documents</h5>
        </div>
        <div class="card-body">
            <!-- Upload Form -->
            <form action="{{ route('registration.upload-document', $candidate->id) }}" method="POST" enctype="multipart/form-data" class="mb-4">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <select name="document_type" class="form-control" required>
                            <option value="">Select Document Type</option>
                            <option value="cnic">CNIC</option>
                            <option value="passport">Passport</option>
                            <option value="education">Education Certificate</option>
                            <option value="police_clearance">Police Clearance</option>
                            <option value="medical">Medical Certificate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="document_number" class="form-control" placeholder="Document #">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="issue_date" class="form-control" placeholder="Issue Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="expiry_date" class="form-control" placeholder="Expiry Date">
                    </div>
                    <div class="col-md-2">
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-upload"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Documents List -->
            @if($candidate->documents->count() > 0)
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Document #</th>
                            <th>Issue Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidate->documents as $doc)
                            <tr>
                                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</span></td>
                                <td class="text-monospace">{{ $doc->document_number ?? '-' }}</td>
                                <td>{{ $doc->issue_date ? $doc->issue_date->format('Y-m-d') : '-' }}</td>
                                <td>{{ $doc->expiry_date ? $doc->expiry_date->format('Y-m-d') : '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $doc->verification_status === 'verified' ? 'success' : 'warning' }}">
                                        {{ ucfirst($doc->verification_status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('registration.delete-document', $doc->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete document?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No documents uploaded yet.
                </div>
            @endif
        </div>
    </div>

    <!-- Next of Kin Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-users"></i> Next of Kin Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('registration.next-of-kin', $candidate->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ $candidate->nextOfKin->name ?? old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Relationship <span class="text-danger">*</span></label>
                            <input type="text" name="relationship" class="form-control"
                                   value="{{ $candidate->nextOfKin->relationship ?? old('relationship') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>CNIC <span class="text-danger">*</span></label>
                            <input type="text" name="cnic" class="form-control"
                                   value="{{ $candidate->nextOfKin->cnic ?? old('cnic') }}"
                                   placeholder="1234567891234" maxlength="13" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ $candidate->nextOfKin->phone ?? old('phone') }}" required>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required>{{ $candidate->nextOfKin->address ?? old('address') }}</textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Next of Kin
                </button>
            </form>
        </div>
    </div>

    <!-- Undertakings Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-signature"></i> Undertakings</h5>
        </div>
        <div class="card-body">
            @if($candidate->undertakings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Signed At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidate->undertakings as $undertaking)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $undertaking->undertaking_type)) }}</td>
                                    <td>{{ $undertaking->signed_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Completed
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    No undertakings signed yet.
                </div>
            @endif
        </div>
    </div>

    <!-- Complete Registration -->
    <div class="card border-success mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Complete Registration</h5>
        </div>
        <div class="card-body">
            @php
                $hasAllDocs = $candidate->documents->whereIn('document_type', ['cnic', 'passport', 'education', 'police_clearance'])->count() >= 4;
                $hasNextOfKin = $candidate->nextOfKin !== null;
                $canComplete = $hasAllDocs && $hasNextOfKin;
            @endphp

            @if(!$hasAllDocs)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Missing required documents: CNIC, Passport, Education Certificate, Police Clearance
                </div>
            @endif

            @if(!$hasNextOfKin)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Next of kin information is required
                </div>
            @endif

            @if($canComplete && $candidate->status !== 'registered')
                <form action="{{ route('registration.complete', $candidate->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Complete registration for this candidate?')">
                        <i class="fas fa-check-double"></i> Complete Registration
                    </button>
                </form>
            @elseif($candidate->status === 'registered')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Registration completed on {{ $candidate->updated_at->format('Y-m-d H:i') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
