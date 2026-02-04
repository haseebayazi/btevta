@extends('layouts.app')
@section('title', 'Registration Details - ' . $candidate->name)
@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('registration.index') }}">Registration</a></li>
                    <li class="breadcrumb-item active">{{ $candidate->btevta_id }}</li>
                </ol>
            </nav>
            <h2 class="mb-0">Registration Management</h2>
            <p class="text-muted mb-0">{{ $candidate->name }} ({{ $candidate->btevta_id }})</p>
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

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    {{-- Module 3: Allocation CTA for screened candidates --}}
    @if(in_array($candidate->status, ['screened', 'screening_passed']))
    <div class="alert alert-success shadow-sm mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1"><i class="fas fa-check-circle mr-2"></i>Candidate Ready for Registration</h5>
                <p class="mb-0">This candidate has been screened. Proceed to allocation to complete registration with Campus, Program, Course, and NOK details.</p>
            </div>
            <a href="{{ route('registration.allocation', $candidate->id) }}" class="btn btn-success btn-lg">
                <i class="fas fa-clipboard-list mr-2"></i>Proceed to Allocation
            </a>
        </div>
    </div>
    @endif

    <div class="row">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Candidate Basic Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong class="text-muted">TheLeap ID:</strong><br>
                            <span class="text-monospace font-weight-bold">{{ $candidate->btevta_id }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong class="text-muted">CNIC:</strong><br>
                            <span class="text-monospace">{{ $candidate->formatted_cnic ?? $candidate->cnic ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong class="text-muted">Status:</strong><br>
                            <span class="badge badge-{{ $candidate->status === \App\Models\Candidate::STATUS_REGISTERED ? 'success' : 'warning' }} px-3 py-2">
                                {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4 mb-2">
                            <strong class="text-muted">Campus:</strong><br>{{ $candidate->campus->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong class="text-muted">Trade:</strong><br>{{ $candidate->trade->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong class="text-muted">Phone:</strong><br>{{ $candidate->phone ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Documents Section --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Document Archive</h5>
                </div>
                <div class="card-body">
                    {{-- Upload Form --}}
                    <form action="{{ route('registration.upload-document', $candidate->id) }}" method="POST" enctype="multipart/form-data" class="mb-4 p-3 bg-light rounded">
                        @csrf
                        <h6 class="mb-3"><i class="fas fa-upload mr-1"></i>Upload New Document</h6>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="small font-weight-bold">Document Type *</label>
                                <select name="document_type" class="form-control form-control-sm @error('document_type') is-invalid @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="cnic">CNIC</option>
                                    <option value="passport">Passport</option>
                                    <option value="education">Education Certificate</option>
                                    <option value="police_clearance">Police Clearance</option>
                                    <option value="medical">Medical Certificate</option>
                                    <option value="photo">Photo</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('document_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small font-weight-bold">Document #</label>
                                <input type="text" name="document_number" class="form-control form-control-sm" placeholder="Optional">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small font-weight-bold">Issue Date</label>
                                <input type="date" name="issue_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small font-weight-bold">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small font-weight-bold">File *</label>
                                <input type="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror" required accept=".pdf,.jpg,.jpeg,.png">
                                @error('file')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-1 mb-2">
                                <label class="small font-weight-bold">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Required Documents Checklist --}}
                    @php
                        $requiredDocs = ['cnic', 'passport', 'education', 'police_clearance', 'medical'];
                        $uploadedTypes = $candidate->documents->pluck('document_type')->toArray();
                    @endphp
                    <div class="mb-3">
                        <h6 class="mb-2">Required Documents:</h6>
                        <div class="d-flex flex-wrap">
                            @foreach($requiredDocs as $docType)
                                <span class="badge mr-2 mb-1 px-2 py-1 {{ in_array($docType, $uploadedTypes) ? 'badge-success' : 'badge-secondary' }}">
                                    <i class="fas {{ in_array($docType, $uploadedTypes) ? 'fa-check' : 'fa-times' }} mr-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $docType)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Documents List --}}
                    @if($candidate->documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="bg-light">
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
                                            <td>{{ $doc->issue_date ? $doc->issue_date->format('d M Y') : '-' }}</td>
                                            <td>
                                                @if($doc->expiry_date)
                                                    <span class="{{ $doc->expiry_date->isPast() ? 'text-danger' : ($doc->expiry_date->diffInDays(now()) < 30 ? 'text-warning' : '') }}">
                                                        {{ $doc->expiry_date->format('d M Y') }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ ($doc->verification_status ?? $doc->status) === 'verified' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($doc->verification_status ?? $doc->status ?? 'pending') }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-xs btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('registration.delete-document', $doc->id) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this document?')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle mr-2"></i>No documents uploaded yet. Please upload required documents.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Next of Kin Section --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Next of Kin Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('registration.next-of-kin', $candidate->id) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $candidate->nextOfKin->name ?? '') }}" required>
                                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Relationship <span class="text-danger">*</span></label>
                                    <select name="relationship" class="form-control @error('relationship') is-invalid @enderror" required>
                                        <option value="">Select</option>
                                        @foreach(['Father', 'Mother', 'Spouse', 'Brother', 'Sister', 'Son', 'Daughter', 'Uncle', 'Aunt', 'Other'] as $rel)
                                            <option value="{{ $rel }}" {{ old('relationship', $candidate->nextOfKin->relationship ?? '') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                        @endforeach
                                    </select>
                                    @error('relationship')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">CNIC <span class="text-danger">*</span></label>
                                    <input type="text" name="cnic" class="form-control @error('cnic') is-invalid @enderror"
                                           value="{{ old('cnic', $candidate->nextOfKin->cnic ?? '') }}"
                                           placeholder="1234567891234" maxlength="13" required>
                                    @error('cnic')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $candidate->nextOfKin->phone ?? '') }}" required>
                                    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Occupation</label>
                                    <input type="text" name="occupation" class="form-control"
                                           value="{{ old('occupation', $candidate->nextOfKin->occupation ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="1" required>{{ old('address', $candidate->nextOfKin->address ?? '') }}</textarea>
                                    @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Save Next of Kin
                        </button>
                        @if($candidate->nextOfKin)
                            <span class="badge badge-success ml-2"><i class="fas fa-check"></i> Saved</span>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Undertakings Section --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-file-signature mr-2"></i>Undertakings & Declarations</h5>
                </div>
                <div class="card-body">
                    {{-- Add Undertaking Form --}}
                    <form action="{{ route('registration.undertaking', $candidate->id) }}" method="POST" enctype="multipart/form-data" class="mb-4 p-3 bg-light rounded">
                        @csrf
                        <h6 class="mb-3"><i class="fas fa-plus mr-1"></i>Sign New Undertaking</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="small font-weight-bold">Undertaking Type <span class="text-danger">*</span></label>
                                <select name="undertaking_type" class="form-control form-control-sm @error('undertaking_type') is-invalid @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="employment">Employment Terms</option>
                                    <option value="financial">Financial Obligations</option>
                                    <option value="behavior">Code of Conduct</option>
                                    <option value="other">Other Declaration</option>
                                </select>
                                @error('undertaking_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="small font-weight-bold">Witness Name</label>
                                <input type="text" name="witness_name" class="form-control form-control-sm" placeholder="Optional">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="small font-weight-bold">Witness CNIC</label>
                                <input type="text" name="witness_cnic" class="form-control form-control-sm" placeholder="Optional" maxlength="13">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-2">
                                <label class="small font-weight-bold">Declaration Content <span class="text-danger">*</span></label>
                                <textarea name="content" class="form-control form-control-sm @error('content') is-invalid @enderror" rows="2" required placeholder="Enter the undertaking/declaration text..."></textarea>
                                @error('content')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="small font-weight-bold">Signature (Image)</label>
                                <input type="file" name="signature" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-signature mr-1"></i>Record Undertaking
                        </button>
                    </form>

                    {{-- Undertakings List --}}
                    @if($candidate->undertakings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Content</th>
                                        <th>Witness</th>
                                        <th>Signed At</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($candidate->undertakings as $undertaking)
                                        <tr>
                                            <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $undertaking->undertaking_type)) }}</span></td>
                                            <td class="small" style="max-width: 300px;">{{ Str::limit($undertaking->content, 100) }}</td>
                                            <td class="small">{{ $undertaking->witness_name ?? '-' }}</td>
                                            <td class="small">{{ $undertaking->signed_at ? $undertaking->signed_at->format('d M Y H:i') : '-' }}</td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Signed
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>No undertakings signed yet. At least one undertaking is required to complete registration.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- OEP Allocation --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-building mr-2"></i>OEP Allocation</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('candidates.update', $candidate->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="font-weight-bold">Overseas Employment Promoter</label>
                            <select name="oep_id" class="form-control @error('oep_id') is-invalid @enderror">
                                <option value="">-- Not Assigned --</option>
                                @foreach(\App\Models\Oep::where('is_active', true)->get() as $oep)
                                    <option value="{{ $oep->id }}" {{ $candidate->oep_id == $oep->id ? 'selected' : '' }}>
                                        {{ $oep->name }} ({{ $oep->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('oep_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            <small class="text-muted">Assign candidate to an OEP based on demand or trade specialization</small>
                        </div>
                        {{-- Include required hidden fields --}}
                        <input type="hidden" name="btevta_id" value="{{ $candidate->btevta_id }}">
                        <input type="hidden" name="name" value="{{ $candidate->name }}">
                        <input type="hidden" name="father_name" value="{{ $candidate->father_name }}">
                        <input type="hidden" name="cnic" value="{{ $candidate->cnic }}">
                        <input type="hidden" name="date_of_birth" value="{{ $candidate->date_of_birth?->format('Y-m-d') }}">
                        <input type="hidden" name="gender" value="{{ $candidate->gender }}">
                        <input type="hidden" name="phone" value="{{ $candidate->phone }}">
                        <input type="hidden" name="email" value="{{ $candidate->email }}">
                        <input type="hidden" name="address" value="{{ $candidate->address }}">
                        <input type="hidden" name="district" value="{{ $candidate->district }}">
                        <input type="hidden" name="trade_id" value="{{ $candidate->trade_id }}">
                        <button type="submit" class="btn btn-info btn-block">
                            <i class="fas fa-save mr-1"></i>Update OEP Assignment
                        </button>
                    </form>
                    @if($candidate->oep)
                        <div class="mt-3 p-2 bg-light rounded">
                            <strong>Current OEP:</strong><br>
                            <span class="text-primary">{{ $candidate->oep->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Registration Checklist --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Registration Checklist</h5>
                </div>
                <div class="card-body">
                    @php
                        $hasAllDocs = $candidate->documents->whereIn('document_type', ['cnic', 'passport', 'education', 'police_clearance'])->count() >= 4;
                        $hasNextOfKin = $candidate->nextOfKin !== null;
                        $hasUndertaking = $candidate->undertakings->count() > 0;
                        $canComplete = $hasAllDocs && $hasNextOfKin && $hasUndertaking;
                    @endphp

                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas {{ $hasAllDocs ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mr-2"></i>
                            Required Documents (CNIC, Passport, Education, Police Clearance)
                        </li>
                        <li class="mb-2">
                            <i class="fas {{ $hasNextOfKin ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mr-2"></i>
                            Next of Kin Information
                        </li>
                        <li class="mb-2">
                            <i class="fas {{ $hasUndertaking ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mr-2"></i>
                            At Least One Undertaking Signed
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Complete Registration --}}
            <div class="card shadow-sm border-success">
                <div class="card-header py-3 bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle mr-2"></i>Complete Registration</h5>
                </div>
                <div class="card-body">
                    @if($candidate->status === \App\Models\Candidate::STATUS_REGISTERED)
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Registration Completed!</strong><br>
                            <small>{{ $candidate->registered_at ? $candidate->registered_at->format('d M Y H:i') : $candidate->updated_at->format('d M Y H:i') }}</small>
                        </div>
                    @elseif($canComplete)
                        <p class="text-muted small">All requirements met. Click below to complete the registration process.</p>
                        <form action="{{ route('registration.complete', $candidate->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block btn-lg" onclick="return confirm('Complete registration for this candidate? This will update their status to Registered.')">
                                <i class="fas fa-check-double mr-1"></i>Complete Registration
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Cannot Complete Yet</strong><br>
                            <small>Please complete all items in the checklist above.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
