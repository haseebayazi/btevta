@extends('layouts.app')

@section('title', 'Edit Visa Process - ' . $candidate->name)

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('visa-processing.index') }}">Visa Processing</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('visa-processing.show', $candidate) }}">{{ $candidate->btevta_id }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h2 class="mb-0">Edit Visa Process</h2>
            <p class="text-muted mb-0">{{ $candidate->name }} - {{ $candidate->trade->name ?? 'N/A' }}</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('visa-processing.show', $candidate) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
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

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @php $visaProcess = $candidate->visaProcess; @endphp

    <div class="row">
        {{-- Left Sidebar --}}
        <div class="col-lg-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Candidate</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $candidate->name }}</strong></p>
                    <p class="mb-1 text-monospace small">{{ $candidate->btevta_id }}</p>
                    <p class="mb-1 small">{{ $candidate->trade->name ?? 'N/A' }}</p>
                    <p class="mb-0 small">{{ $candidate->oep->name ?? 'No OEP' }}</p>
                </div>
            </div>

            {{-- Stage Navigation --}}
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Quick Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#stage-interview" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Interview & Trade Test
                        <i class="fas {{ $visaProcess->interview_completed ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-takamol" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Takamol Test
                        <i class="fas {{ $visaProcess->takamol_status === 'completed' ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-medical" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Medical (GAMCA)
                        <i class="fas {{ $visaProcess->medical_completed ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-enumber" class="list-group-item list-group-item-action d-flex justify-content-between">
                        E-Number
                        <i class="fas {{ $visaProcess->enumber ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-biometric" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Biometrics (Etimad)
                        <i class="fas {{ $visaProcess->biometric_completed ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-submission" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Visa Submission
                        <i class="fas {{ $visaProcess->visa_submission_date ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-visa" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Visa & PTN
                        <i class="fas {{ $visaProcess->visa_issued ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                    <a href="#stage-ticket" class="list-group-item list-group-item-action d-flex justify-content-between">
                        Ticket & Travel
                        <i class="fas {{ $visaProcess->ticket_uploaded ? 'fa-check text-success' : 'fa-clock text-warning' }}"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="col-lg-9">
            {{-- Stage 1: Interview & Trade Test --}}
            <div class="card shadow-sm mb-4" id="stage-interview">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>1. Interview & Trade Test</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Interview --}}
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Interview</h6>
                            <form action="{{ route('visa-processing.update-interview', $candidate) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Interview Date <span class="text-danger">*</span></label>
                                    <input type="date" name="interview_date" class="form-control" value="{{ $visaProcess->interview_date ? $visaProcess->interview_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="interview_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->interview_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="passed" {{ $visaProcess->interview_status === 'passed' ? 'selected' : '' }}>Passed</option>
                                        <option value="failed" {{ $visaProcess->interview_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="interview_remarks" class="form-control" rows="2">{{ $visaProcess->interview_remarks }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Interview</button>
                            </form>
                        </div>

                        {{-- Trade Test --}}
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Trade Test</h6>
                            <form action="{{ route('visa-processing.update-trade-test', $candidate) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Trade Test Date <span class="text-danger">*</span></label>
                                    <input type="date" name="trade_test_date" class="form-control" value="{{ $visaProcess->trade_test_date ? $visaProcess->trade_test_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="trade_test_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->trade_test_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="passed" {{ $visaProcess->trade_test_status === 'passed' ? 'selected' : '' }}>Passed</option>
                                        <option value="failed" {{ $visaProcess->trade_test_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="trade_test_remarks" class="form-control" rows="2">{{ $visaProcess->trade_test_remarks }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Trade Test</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 2: Takamol Test --}}
            <div class="card shadow-sm mb-4" id="stage-takamol">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check mr-2"></i>2. Takamol Test</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('visa-processing.update-takamol', $candidate) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Booking Date</label>
                                    <input type="date" name="takamol_booking_date" class="form-control" value="{{ $visaProcess->takamol_booking_date ? $visaProcess->takamol_booking_date->format('Y-m-d') : '' }}">
                                </div>
                                <div class="form-group">
                                    <label>Test Date <span class="text-danger">*</span></label>
                                    <input type="date" name="takamol_date" class="form-control" value="{{ $visaProcess->takamol_date ? $visaProcess->takamol_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="takamol_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->takamol_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="completed" {{ $visaProcess->takamol_status === 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="failed" {{ $visaProcess->takamol_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="takamol_remarks" class="form-control" rows="2">{{ $visaProcess->takamol_remarks }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Takamol</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Upload Result</h6>
                            <form action="{{ route('visa-processing.upload-takamol-result', $candidate) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Result File <span class="text-danger">*</span></label>
                                    <input type="file" name="takamol_result_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">PDF, JPG, PNG - Max 5MB</small>
                                </div>
                                <div class="form-group">
                                    <label>Score</label>
                                    <input type="number" name="takamol_score" class="form-control" min="0" max="100" value="{{ $visaProcess->takamol_score }}">
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="takamol_status" class="form-control" required>
                                        <option value="completed">Completed/Pass</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload"></i> Upload Result</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 3: Medical/GAMCA --}}
            <div class="card shadow-sm mb-4" id="stage-medical">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat mr-2"></i>3. Medical (GAMCA)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('visa-processing.update-medical', $candidate) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Booking Date</label>
                                    <input type="date" name="gamca_booking_date" class="form-control" value="{{ $visaProcess->gamca_booking_date ? $visaProcess->gamca_booking_date->format('Y-m-d') : '' }}">
                                </div>
                                <div class="form-group">
                                    <label>Medical Date <span class="text-danger">*</span></label>
                                    <input type="date" name="medical_date" class="form-control" value="{{ $visaProcess->medical_date ? $visaProcess->medical_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="medical_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->medical_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="fit" {{ $visaProcess->medical_status === 'fit' ? 'selected' : '' }}>Fit</option>
                                        <option value="unfit" {{ $visaProcess->medical_status === 'unfit' ? 'selected' : '' }}>Unfit</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="medical_remarks" class="form-control" rows="2">{{ $visaProcess->medical_remarks }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Medical</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Upload GAMCA Result</h6>
                            <form action="{{ route('visa-processing.upload-gamca-result', $candidate) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>GAMCA Certificate <span class="text-danger">*</span></label>
                                    <input type="file" name="gamca_result_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <div class="form-group">
                                    <label>GAMCA Barcode</label>
                                    <input type="text" name="gamca_barcode" class="form-control" value="{{ $visaProcess->gamca_barcode }}">
                                </div>
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input type="date" name="gamca_expiry_date" class="form-control" value="{{ $visaProcess->gamca_expiry_date ? $visaProcess->gamca_expiry_date->format('Y-m-d') : '' }}">
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="medical_status" class="form-control" required>
                                        <option value="fit">Fit</option>
                                        <option value="unfit">Unfit</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload"></i> Upload GAMCA</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 4: E-Number --}}
            <div class="card shadow-sm mb-4" id="stage-enumber">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-hashtag mr-2"></i>4. E-Number Generation</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('visa-processing.update-enumber', $candidate) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>E-Number</label>
                                    <input type="text" name="enumber" class="form-control text-monospace" value="{{ $visaProcess->enumber }}" placeholder="Leave blank to auto-generate">
                                    <small class="text-muted">Leave blank to auto-generate</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Generation Date <span class="text-danger">*</span></label>
                                    <input type="date" name="enumber_date" class="form-control" value="{{ $visaProcess->enumber_date ? $visaProcess->enumber_date->format('Y-m-d') : date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="enumber_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->enumber_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="generated" {{ $visaProcess->enumber_status === 'generated' ? 'selected' : '' }}>Generated</option>
                                        <option value="verified" {{ $visaProcess->enumber_status === 'verified' ? 'selected' : '' }}>Verified</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Generate/Update E-Number</button>
                    </form>
                </div>
            </div>

            {{-- Stage 5: Biometrics/Etimad --}}
            <div class="card shadow-sm mb-4" id="stage-biometric">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-fingerprint mr-2"></i>5. Biometrics (Etimad)</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('visa-processing.update-biometric', $candidate) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Etimad Appointment ID</label>
                                    <input type="text" name="etimad_appointment_id" class="form-control text-monospace" value="{{ $visaProcess->etimad_appointment_id }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Appointment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="biometric_date" class="form-control" value="{{ $visaProcess->biometric_date ? $visaProcess->biometric_date->format('Y-m-d') : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Etimad Center</label>
                                    <input type="text" name="etimad_center" class="form-control" value="{{ $visaProcess->etimad_center }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="biometric_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->biometric_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="completed" {{ $visaProcess->biometric_status === 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="failed" {{ $visaProcess->biometric_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="biometric_remarks" class="form-control" rows="2">{{ $visaProcess->biometric_remarks }}</textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Biometrics</button>
                    </form>
                </div>
            </div>

            {{-- Stage 6: Visa Documents Submission --}}
            <div class="card shadow-sm mb-4" id="stage-submission">
                <div class="card-header py-3 bg-warning">
                    <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>6. Visa Documents Submission</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('visa-processing.update-visa-submission', $candidate) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Submission Date <span class="text-danger">*</span></label>
                                    <input type="date" name="visa_submission_date" class="form-control" value="{{ $visaProcess->visa_submission_date ? $visaProcess->visa_submission_date->format('Y-m-d') : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Application Number</label>
                                    <input type="text" name="visa_application_number" class="form-control text-monospace" value="{{ $visaProcess->visa_application_number }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Embassy</label>
                                    <input type="text" name="embassy" class="form-control" value="{{ $visaProcess->embassy }}" placeholder="e.g., Saudi Embassy Islamabad">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Submission Details</button>
                    </form>
                </div>
            </div>

            {{-- Stage 7: Visa & PTN --}}
            <div class="card shadow-sm mb-4" id="stage-visa">
                <div class="card-header py-3 bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-passport mr-2"></i>7. Visa & PTN Issuance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Visa --}}
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Visa Details</h6>
                            <form action="{{ route('visa-processing.update-visa', $candidate) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Visa Date <span class="text-danger">*</span></label>
                                    <input type="date" name="visa_date" class="form-control" value="{{ $visaProcess->visa_date ? $visaProcess->visa_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Visa Number <span class="text-danger">*</span></label>
                                    <input type="text" name="visa_number" class="form-control text-monospace" value="{{ $visaProcess->visa_number }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="visa_status" class="form-control" required>
                                        <option value="pending" {{ $visaProcess->visa_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="issued" {{ $visaProcess->visa_status === 'issued' ? 'selected' : '' }}>Issued</option>
                                        <option value="rejected" {{ $visaProcess->visa_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="visa_remarks" class="form-control" rows="2">{{ $visaProcess->visa_remarks }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Visa</button>
                            </form>
                        </div>

                        {{-- PTN --}}
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">PTN Details</h6>
                            <form action="{{ route('visa-processing.update-ptn', $candidate) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>PTN Number</label>
                                    <input type="text" name="ptn_number" class="form-control text-monospace" value="{{ $visaProcess->ptn_number }}" placeholder="Leave blank to auto-generate">
                                    <small class="text-muted">Leave blank to auto-generate</small>
                                </div>
                                <div class="form-group">
                                    <label>PTN Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="ptn_issue_date" class="form-control" value="{{ $visaProcess->ptn_issue_date ? $visaProcess->ptn_issue_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Attestation Date</label>
                                    <input type="date" name="attestation_date" class="form-control" value="{{ $visaProcess->attestation_date ? $visaProcess->attestation_date->format('Y-m-d') : '' }}">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save PTN</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 8: Ticket & Travel --}}
            <div class="card shadow-sm mb-4" id="stage-ticket">
                <div class="card-header py-3 bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plane-departure mr-2"></i>8. Ticket & Travel Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Upload Ticket</h6>
                            <form action="{{ route('visa-processing.upload-ticket', $candidate) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Ticket File <span class="text-danger">*</span></label>
                                    <input type="file" name="ticket_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <div class="form-group">
                                    <label>Ticket Date <span class="text-danger">*</span></label>
                                    <input type="date" name="ticket_date" class="form-control" value="{{ $visaProcess->ticket_date ? $visaProcess->ticket_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload"></i> Upload Ticket</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold border-bottom pb-2 mb-3">Upload Travel Plan</h6>
                            <form action="{{ route('visa-processing.upload-travel-plan', $candidate) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Travel Plan File <span class="text-danger">*</span></label>
                                    <input type="file" name="travel_plan_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <div class="form-group">
                                    <label>Flight Number</label>
                                    <input type="text" name="flight_number" class="form-control text-monospace" value="{{ $visaProcess->flight_number }}">
                                </div>
                                <div class="form-group">
                                    <label>Departure Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="departure_date" class="form-control" value="{{ $visaProcess->departure_date ? $visaProcess->departure_date->format('Y-m-d\TH:i') : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Arrival Date</label>
                                    <input type="datetime-local" name="arrival_date" class="form-control" value="{{ $visaProcess->arrival_date ? $visaProcess->arrival_date->format('Y-m-d\TH:i') : '' }}">
                                </div>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload"></i> Upload Travel Plan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#stage-"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
</script>
@endpush
@endsection
