@extends('layouts.app')

@section('title', 'Training Details - ' . $candidate->name)

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('training.index') }}">Training</a></li>
                    <li class="breadcrumb-item active">{{ $candidate->btevta_id }}</li>
                </ol>
            </nav>
            <h2 class="mb-0">Training Details</h2>
            <p class="text-muted mb-0">{{ $candidate->name }} - {{ $candidate->batch->name ?? 'No Batch Assigned' }}</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('training.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('training.edit', $candidate) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
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

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Attendance Rate</h6>
                            <h3 class="mb-0">{{ $attendanceStats['percentage'] ?? 0 }}%</h3>
                        </div>
                        <i class="fas fa-clipboard-check fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Present Days</h6>
                            <h3 class="mb-0">{{ $attendanceStats['present'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Absent Days</h6>
                            <h3 class="mb-0">{{ $attendanceStats['absent'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Late / Leave</h6>
                            <h3 class="mb-0">{{ ($attendanceStats['late'] ?? 0) + ($attendanceStats['leave'] ?? 0) }}</h3>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left Column - Main Content --}}
        <div class="col-lg-8">
            {{-- Candidate Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">BTEVTA ID</strong>
                            <span class="font-weight-bold text-monospace">{{ $candidate->btevta_id }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Name</strong>
                            {{ $candidate->name }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">CNIC</strong>
                            <span class="text-monospace">{{ $candidate->formatted_cnic ?? $candidate->cnic }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Trade</strong>
                            {{ $candidate->trade->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Campus</strong>
                            {{ $candidate->campus->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Training Status</strong>
                            <span class="badge badge-{{ $candidate->status === 'training' ? 'info' : ($candidate->status === 'training_completed' ? 'success' : 'secondary') }}">
                                {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Batch Information --}}
            @if($candidate->batch)
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Batch Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Batch Code</strong>
                            <span class="text-monospace">{{ $candidate->batch->batch_code }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Batch Name</strong>
                            {{ $candidate->batch->name }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Batch Status</strong>
                            <span class="badge badge-{{ $candidate->batch->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($candidate->batch->status) }}
                            </span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Start Date</strong>
                            {{ $candidate->batch->start_date ? $candidate->batch->start_date->format('d M Y') : 'Not Set' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">End Date</strong>
                            {{ $candidate->batch->end_date ? $candidate->batch->end_date->format('d M Y') : 'Not Set' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong class="text-muted d-block">Duration</strong>
                            {{ $candidate->batch->duration_in_days ?? 'N/A' }} days
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Attendance History --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Attendance History</h5>
                    <a href="{{ route('training.mark-attendance', $candidate) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Mark Attendance
                    </a>
                </div>
                <div class="card-body">
                    @if($candidate->attendances && $candidate->attendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($candidate->attendances->take(10) as $attendance)
                                        <tr>
                                            <td>{{ $attendance->date ? $attendance->date->format('d M Y') : 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'absent' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($attendance->status) }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ $attendance->detailed_remarks ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($candidate->attendances->count() > 10)
                            <div class="text-center mt-2">
                                <span class="text-muted">Showing 10 of {{ $candidate->attendances->count() }} records</span>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>No attendance records found.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assessment History --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Assessment History</h5>
                    <a href="{{ route('training.assessment-view', $candidate) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Record Assessment
                    </a>
                </div>
                <div class="card-body">
                    @if($candidate->assessments && $candidate->assessments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Score</th>
                                        <th>Result</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($candidate->assessments as $assessment)
                                        <tr>
                                            <td><span class="badge badge-secondary">{{ ucfirst($assessment->assessment_type) }}</span></td>
                                            <td>{{ $assessment->assessment_date ? $assessment->assessment_date->format('d M Y') : 'N/A' }}</td>
                                            <td>
                                                <strong>{{ $assessment->total_score }}</strong> / {{ $assessment->max_score }}
                                                ({{ round(($assessment->total_score / $assessment->max_score) * 100) }}%)
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $assessment->result === 'pass' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($assessment->result) }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ Str::limit($assessment->remarks, 30) ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>No assessment records found.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column - Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt mr-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('training.mark-attendance', $candidate) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-clipboard-check text-success mr-3"></i>
                            <div>
                                <strong>Mark Attendance</strong>
                                <small class="text-muted d-block">Record today's attendance</small>
                            </div>
                        </a>
                        <a href="{{ route('training.assessment-view', $candidate) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-chart-bar text-primary mr-3"></i>
                            <div>
                                <strong>Conduct Assessment</strong>
                                <small class="text-muted d-block">Record theory or practical exam</small>
                            </div>
                        </a>
                        @if($candidate->certificate)
                            <a href="{{ route('training.download-certificate', $candidate) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-certificate text-warning mr-3"></i>
                                <div>
                                    <strong>Download Certificate</strong>
                                    <small class="text-muted d-block">{{ $candidate->certificate->certificate_number }}</small>
                                </div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Certificate Status --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-certificate mr-2"></i>Certificate Status</h5>
                </div>
                <div class="card-body">
                    @if($candidate->certificate)
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Certificate Issued</strong>
                        </div>
                        <p class="mb-1"><strong>Certificate #:</strong> {{ $candidate->certificate->certificate_number }}</p>
                        <p class="mb-1"><strong>Issue Date:</strong> {{ $candidate->certificate->issue_date ? $candidate->certificate->issue_date->format('d M Y') : 'N/A' }}</p>
                        <a href="{{ route('training.download-certificate', $candidate) }}" class="btn btn-warning btn-block mt-3">
                            <i class="fas fa-download mr-1"></i>Download Certificate
                        </a>
                    @else
                        @php
                            $hasPassed = $candidate->assessments && $candidate->assessments->where('assessment_type', 'final')->where('result', 'pass')->count() > 0;
                        @endphp
                        @if($hasPassed)
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                Eligible for certificate
                            </div>
                            <form action="{{ route('training.certificate', $candidate) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-certificate mr-1"></i>Generate Certificate
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Not Eligible</strong><br>
                                <small>Must pass final assessment to receive certificate.</small>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Training Progress --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Training Progress</h5>
                </div>
                <div class="card-body">
                    @php
                        $attendancePercentage = $attendanceStats['percentage'] ?? 0;
                        $hasInitialAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'initial')->count() > 0;
                        $hasMidtermAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'midterm')->count() > 0;
                        $hasPracticalAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'practical')->count() > 0;
                        $hasFinalAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'final')->count() > 0;
                    @endphp

                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas {{ $attendancePercentage >= 80 ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mr-2"></i>
                            Attendance (80% required)
                            <span class="float-right badge badge-{{ $attendancePercentage >= 80 ? 'success' : 'danger' }}">{{ $attendancePercentage }}%</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas {{ $hasInitialAssessment ? 'fa-check-circle text-success' : 'fa-circle text-secondary' }} mr-2"></i>
                            Initial Assessment
                        </li>
                        <li class="mb-2">
                            <i class="fas {{ $hasMidtermAssessment ? 'fa-check-circle text-success' : 'fa-circle text-secondary' }} mr-2"></i>
                            Midterm Assessment
                        </li>
                        <li class="mb-2">
                            <i class="fas {{ $hasPracticalAssessment ? 'fa-check-circle text-success' : 'fa-circle text-secondary' }} mr-2"></i>
                            Practical Assessment
                        </li>
                        <li class="mb-2">
                            <i class="fas {{ $hasFinalAssessment ? 'fa-check-circle text-success' : 'fa-circle text-secondary' }} mr-2"></i>
                            Final Assessment
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Complete Training --}}
            @if($candidate->status === 'training')
                <div class="card shadow-sm border-success">
                    <div class="card-header py-3 bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-graduation-cap mr-2"></i>Complete Training</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $canComplete = $attendancePercentage >= 80 && $hasFinalAssessment && $candidate->assessments->where('assessment_type', 'final')->where('result', 'pass')->count() > 0;
                        @endphp

                        @if($canComplete)
                            <p class="text-muted small">All requirements met. Complete the training to move to the next stage.</p>
                            <form action="{{ route('training.complete', $candidate) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Mark training as complete for this candidate?')">
                                    <i class="fas fa-check-double mr-1"></i>Complete Training
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Cannot Complete</strong><br>
                                <small>
                                    Requirements:
                                    <ul class="mb-0 pl-3 mt-1">
                                        <li>Minimum 80% attendance</li>
                                        <li>Pass final assessment</li>
                                    </ul>
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
