@extends('layouts.app')

@section('title', 'Visa Processing - ' . $candidate->name)

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('visa-processing.index') }}">Visa Processing</a></li>
                    <li class="breadcrumb-item active">{{ $candidate->btevta_id }}</li>
                </ol>
            </nav>
            <h2 class="mb-0">Visa Processing</h2>
            <p class="text-muted mb-0">{{ $candidate->name }} - {{ $candidate->trade->name ?? 'N/A' }}</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('visa-processing.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="{{ route('visa-processing.timeline', $candidate) }}" class="btn btn-info">
                <i class="fas fa-history"></i> Timeline
            </a>
            @if($candidate->visaProcess && $candidate->visaProcess->overall_status !== 'completed')
                <a href="{{ route('visa-processing.edit', $candidate) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
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

    @php
        $visaProcess = $candidate->visaProcess;
        $stages = \App\Models\VisaProcess::getStages();
    @endphp

    {{-- Progress Bar --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Processing Progress</h5>
                <span class="badge badge-{{ $visaProcess->status_color ?? 'secondary' }} px-3 py-2">
                    {{ $stages[$visaProcess->overall_status]['label'] ?? 'Initiated' }}
                </span>
            </div>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $visaProcess->progress_percentage ?? 10 }}%">
                    {{ $visaProcess->progress_percentage ?? 10 }}%
                </div>
            </div>
            <div class="row mt-3">
                @foreach($stages as $key => $stage)
                    @if($key !== 'completed')
                    <div class="col text-center">
                        @php
                            $isCompleted = $visaProcess ? $visaProcess->isStageCompleted($key) : false;
                            $isCurrent = $visaProcess && $visaProcess->overall_status === $key;
                        @endphp
                        <div class="rounded-circle d-inline-flex justify-content-center align-items-center {{ $isCompleted ? 'bg-success' : ($isCurrent ? 'bg-primary' : 'bg-secondary') }} text-white" style="width: 30px; height: 30px;">
                            @if($isCompleted)
                                <i class="fas fa-check"></i>
                            @else
                                {{ $stage['order'] }}
                            @endif
                        </div>
                        <p class="small mb-0 mt-1 {{ $isCompleted || $isCurrent ? 'font-weight-bold' : 'text-muted' }}">{{ $stage['label'] }}</p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left Column - Candidate Info --}}
        <div class="col-lg-4">
            {{-- Candidate Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="{{ $candidate->photo_url ?? asset('img/default.png') }}" class="rounded-circle" width="100" height="100" alt="{{ $candidate->name }}">
                    </div>
                    <table class="table table-sm table-borderless">
                        <tr><td class="text-muted">TheLeap ID</td><td class="text-monospace font-weight-bold">{{ $candidate->btevta_id }}</td></tr>
                        <tr><td class="text-muted">Name</td><td>{{ $candidate->name }}</td></tr>
                        <tr><td class="text-muted">CNIC</td><td class="text-monospace">{{ $candidate->cnic }}</td></tr>
                        <tr><td class="text-muted">Passport</td><td class="text-monospace">{{ $candidate->passport_number ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Trade</td><td>{{ $candidate->trade->name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">Campus</td><td>{{ $candidate->campus->name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted">OEP</td><td>{{ $candidate->oep->name ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Key Numbers --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Key Numbers</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small d-block">E-Number</label>
                        <span class="h5 text-monospace">{{ $visaProcess->enumber ?? 'Not Generated' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small d-block">PTN Number</label>
                        <span class="h5 text-monospace">{{ $visaProcess->ptn_number ?? 'Not Issued' }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Visa Number</label>
                        <span class="h5 text-monospace">{{ $visaProcess->visa_number ?? 'Not Issued' }}</span>
                    </div>
                </div>
            </div>

            {{-- Complete Button --}}
            @if($visaProcess && $visaProcess->ticket_uploaded && $visaProcess->overall_status !== 'completed')
                <div class="card shadow-sm border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>Ready to Complete</h5>
                        <p class="text-muted small">All stages are completed. Mark this visa process as complete.</p>
                        <form action="{{ route('visa-processing.complete', $candidate) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg btn-block" onclick="return confirm('Mark visa process as complete?')">
                                <i class="fas fa-check-double mr-2"></i>Complete Process
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column - Stages --}}
        <div class="col-lg-8">
            {{-- Stage 1: Interview & Trade Test --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>1. Interview & Trade Test</h5>
                    <span class="badge badge-{{ $visaProcess->interview_completed && $visaProcess->trade_test_completed ? 'success' : 'warning' }}">
                        {{ $visaProcess->interview_completed && $visaProcess->trade_test_completed ? 'Completed' : 'Pending' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Interview</h6>
                            <p class="mb-1"><strong>Date:</strong> {{ $visaProcess->interview_date ? $visaProcess->interview_date->format('d M Y') : 'Not Scheduled' }}</p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="badge badge-{{ $visaProcess->interview_status === 'passed' ? 'success' : ($visaProcess->interview_status === 'failed' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($visaProcess->interview_status ?? 'pending') }}
                                </span>
                            </p>
                            @if($visaProcess->interview_remarks)
                                <p class="small text-muted mb-0"><strong>Remarks:</strong> {{ $visaProcess->interview_remarks }}</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Trade Test</h6>
                            <p class="mb-1"><strong>Date:</strong> {{ $visaProcess->trade_test_date ? $visaProcess->trade_test_date->format('d M Y') : 'Not Scheduled' }}</p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="badge badge-{{ $visaProcess->trade_test_status === 'passed' ? 'success' : ($visaProcess->trade_test_status === 'failed' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($visaProcess->trade_test_status ?? 'pending') }}
                                </span>
                            </p>
                            @if($visaProcess->trade_test_remarks)
                                <p class="small text-muted mb-0"><strong>Remarks:</strong> {{ $visaProcess->trade_test_remarks }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 2: Takamol Test --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check mr-2"></i>2. Takamol Test</h5>
                    <span class="badge badge-{{ $visaProcess->takamol_status === 'completed' ? 'success' : 'warning' }}">
                        {{ ucfirst($visaProcess->takamol_status ?? 'pending') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Booking Date:</strong> {{ $visaProcess->takamol_booking_date ? $visaProcess->takamol_booking_date->format('d M Y') : 'Not Booked' }}</p>
                            <p class="mb-1"><strong>Test Date:</strong> {{ $visaProcess->takamol_date ? $visaProcess->takamol_date->format('d M Y') : 'Not Scheduled' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Score:</strong> {{ $visaProcess->takamol_score ?? 'N/A' }}</p>
                            @if($visaProcess->takamol_result_path)
                                <p class="mb-1"><strong>Result:</strong>
                                    <a href="{{ asset('storage/' . $visaProcess->takamol_result_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> View Result
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 3: Medical/GAMCA --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-heartbeat mr-2"></i>3. Medical (GAMCA)</h5>
                    <span class="badge badge-{{ $visaProcess->medical_completed ? 'success' : 'warning' }}">
                        {{ $visaProcess->medical_status === 'fit' ? 'Fit' : ($visaProcess->medical_status === 'unfit' ? 'Unfit' : 'Pending') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Booking Date:</strong> {{ $visaProcess->gamca_booking_date ? $visaProcess->gamca_booking_date->format('d M Y') : 'Not Booked' }}</p>
                            <p class="mb-1"><strong>Test Date:</strong> {{ $visaProcess->medical_date ? $visaProcess->medical_date->format('d M Y') : 'Not Scheduled' }}</p>
                            <p class="mb-1"><strong>Barcode:</strong> <span class="text-monospace">{{ $visaProcess->gamca_barcode ?? 'N/A' }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Expiry Date:</strong> {{ $visaProcess->gamca_expiry_date ? $visaProcess->gamca_expiry_date->format('d M Y') : 'N/A' }}</p>
                            @if($visaProcess->gamca_result_path)
                                <p class="mb-1"><strong>Result:</strong>
                                    <a href="{{ asset('storage/' . $visaProcess->gamca_result_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> View Certificate
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 4: E-Number --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-hashtag mr-2"></i>4. E-Number Generation</h5>
                    <span class="badge badge-{{ $visaProcess->enumber ? 'success' : 'warning' }}">
                        {{ $visaProcess->enumber ? 'Generated' : 'Pending' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>E-Number:</strong> <span class="text-monospace h5">{{ $visaProcess->enumber ?? 'Not Generated' }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Generation Date:</strong> {{ $visaProcess->enumber_date ? $visaProcess->enumber_date->format('d M Y') : 'N/A' }}</p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="badge badge-{{ $visaProcess->enumber_status === 'verified' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($visaProcess->enumber_status ?? 'pending') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 5: Biometrics/Etimad --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-fingerprint mr-2"></i>5. Biometrics (Etimad)</h5>
                    <span class="badge badge-{{ $visaProcess->biometric_completed ? 'success' : 'warning' }}">
                        {{ $visaProcess->biometric_completed ? 'Completed' : 'Pending' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Appointment ID:</strong> <span class="text-monospace">{{ $visaProcess->etimad_appointment_id ?? 'N/A' }}</span></p>
                            <p class="mb-1"><strong>Appointment Date:</strong> {{ $visaProcess->biometric_date ? $visaProcess->biometric_date->format('d M Y') : 'Not Scheduled' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Center:</strong> {{ $visaProcess->etimad_center ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="badge badge-{{ $visaProcess->biometric_status === 'completed' ? 'success' : ($visaProcess->biometric_status === 'failed' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($visaProcess->biometric_status ?? 'pending') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 6: Visa Documents Submission --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>6. Visa Documents Submission</h5>
                    <span class="badge badge-{{ $visaProcess->visa_submission_date ? 'success' : 'warning' }}">
                        {{ $visaProcess->visa_submission_date ? 'Submitted' : 'Pending' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Submission Date:</strong> {{ $visaProcess->visa_submission_date ? $visaProcess->visa_submission_date->format('d M Y') : 'Not Submitted' }}</p>
                            <p class="mb-1"><strong>Application No:</strong> <span class="text-monospace">{{ $visaProcess->visa_application_number ?? 'N/A' }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Embassy:</strong> {{ $visaProcess->embassy ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 7: Visa & PTN --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-passport mr-2"></i>7. Visa & PTN Issuance</h5>
                    <span class="badge badge-{{ $visaProcess->visa_issued ? 'success' : 'warning' }}">
                        {{ $visaProcess->visa_issued ? 'Issued' : 'Pending' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Visa</h6>
                            <p class="mb-1"><strong>Visa Number:</strong> <span class="text-monospace">{{ $visaProcess->visa_number ?? 'Not Issued' }}</span></p>
                            <p class="mb-1"><strong>Issue Date:</strong> {{ $visaProcess->visa_date ? $visaProcess->visa_date->format('d M Y') : 'N/A' }}</p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="badge badge-{{ $visaProcess->visa_status === 'issued' ? 'success' : ($visaProcess->visa_status === 'rejected' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($visaProcess->visa_status ?? 'pending') }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">PTN</h6>
                            <p class="mb-1"><strong>PTN Number:</strong> <span class="text-monospace">{{ $visaProcess->ptn_number ?? 'Not Issued' }}</span></p>
                            <p class="mb-1"><strong>Issue Date:</strong> {{ $visaProcess->ptn_issue_date ? $visaProcess->ptn_issue_date->format('d M Y') : 'N/A' }}</p>
                            <p class="mb-1"><strong>Attestation Date:</strong> {{ $visaProcess->attestation_date ? $visaProcess->attestation_date->format('d M Y') : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 8: Ticket & Travel --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plane-departure mr-2"></i>8. Ticket & Travel Plan</h5>
                    <span class="badge badge-{{ $visaProcess->ticket_uploaded ? 'success' : 'warning' }}">
                        {{ $visaProcess->ticket_uploaded ? 'Uploaded' : 'Pending' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Ticket Date:</strong> {{ $visaProcess->ticket_date ? $visaProcess->ticket_date->format('d M Y') : 'N/A' }}</p>
                            <p class="mb-1"><strong>Flight Number:</strong> <span class="text-monospace">{{ $visaProcess->flight_number ?? 'N/A' }}</span></p>
                            @if($visaProcess->ticket_path)
                                <p class="mb-1"><strong>Ticket:</strong>
                                    <a href="{{ asset('storage/' . $visaProcess->ticket_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> View Ticket
                                    </a>
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Departure:</strong> {{ $visaProcess->departure_date ? $visaProcess->departure_date->format('d M Y H:i') : 'N/A' }}</p>
                            <p class="mb-1"><strong>Arrival:</strong> {{ $visaProcess->arrival_date ? $visaProcess->arrival_date->format('d M Y H:i') : 'N/A' }}</p>
                            @if($visaProcess->travel_plan_path)
                                <p class="mb-1"><strong>Travel Plan:</strong>
                                    <a href="{{ asset('storage/' . $visaProcess->travel_plan_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> View Plan
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
