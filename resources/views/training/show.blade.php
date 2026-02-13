@extends('layouts.app')

@section('title', 'Training Details - ' . $candidate->name)

@section('content')
<div class="py-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.index') }}" class="hover:text-blue-600">Training</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">{{ $candidate->btevta_id }}</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-800">Training Details</h2>
            <p class="text-gray-500 text-sm">{{ $candidate->name }} - {{ $candidate->batch->name ?? 'No Batch Assigned' }}</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
            <a href="{{ route('training.edit', $candidate) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    {{-- Module 4: Dual-Status Training Progress Link --}}
    @if($candidate->training)
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between">
        <div>
            <i class="fas fa-chart-line mr-2"></i>
            <strong>Dual-Status Training:</strong> Track Technical and Soft Skills progress separately.
        </div>
        <a href="{{ route('training.candidate-progress', $candidate->training) }}" class="bg-cyan-500 hover:bg-cyan-600 text-white px-3 py-1.5 rounded-lg text-sm">
            <i class="fas fa-chart-bar mr-1"></i> View Dual-Status Progress
        </a>
    </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-600 text-white rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-blue-200 text-sm mb-1">Attendance Rate</h6>
                    <h3 class="text-2xl font-bold">{{ $attendanceStats['percentage'] ?? 0 }}%</h3>
                </div>
                <i class="fas fa-clipboard-check fa-2x text-white/50"></i>
            </div>
        </div>
        <div class="bg-green-600 text-white rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-green-200 text-sm mb-1">Present Days</h6>
                    <h3 class="text-2xl font-bold">{{ $attendanceStats['present'] ?? 0 }}</h3>
                </div>
                <i class="fas fa-check-circle fa-2x text-white/50"></i>
            </div>
        </div>
        <div class="bg-red-600 text-white rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-red-200 text-sm mb-1">Absent Days</h6>
                    <h3 class="text-2xl font-bold">{{ $attendanceStats['absent'] ?? 0 }}</h3>
                </div>
                <i class="fas fa-times-circle fa-2x text-white/50"></i>
            </div>
        </div>
        <div class="bg-yellow-500 text-white rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-yellow-100 text-sm mb-1">Late / Leave</h6>
                    <h3 class="text-2xl font-bold">{{ ($attendanceStats['late'] ?? 0) + ($attendanceStats['leave'] ?? 0) }}</h3>
                </div>
                <i class="fas fa-clock fa-2x text-white/50"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Left Column - Main Content --}}
        <div class="lg:col-span-8">
            {{-- Candidate Info --}}
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="bg-blue-600 text-white px-5 py-3 border-b">
                    <h5 class="font-semibold"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <strong class="text-gray-500 block text-sm">TheLeap ID</strong>
                            <span class="font-bold font-mono">{{ $candidate->btevta_id }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Name</strong>
                            {{ $candidate->name }}
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">CNIC</strong>
                            <span class="font-mono">{{ $candidate->formatted_cnic ?? $candidate->cnic }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Trade</strong>
                            {{ $candidate->trade->name ?? 'N/A' }}
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Campus</strong>
                            {{ $candidate->campus->name ?? 'N/A' }}
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Training Status</strong>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $candidate->status === 'training' ? 'bg-blue-100 text-blue-800' : ($candidate->status === 'training_completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Batch Information --}}
            @if($candidate->batch)
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-users mr-2"></i>Batch Information</h5>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <strong class="text-gray-500 block text-sm">Batch Code</strong>
                            <span class="font-mono">{{ $candidate->batch->batch_code }}</span>
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Batch Name</strong>
                            {{ $candidate->batch->name }}
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Batch Status</strong>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $candidate->batch->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($candidate->batch->status) }}
                            </span>
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Start Date</strong>
                            {{ $candidate->batch->start_date ? $candidate->batch->start_date->format('d M Y') : 'Not Set' }}
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">End Date</strong>
                            {{ $candidate->batch->end_date ? $candidate->batch->end_date->format('d M Y') : 'Not Set' }}
                        </div>
                        <div>
                            <strong class="text-gray-500 block text-sm">Duration</strong>
                            {{ $candidate->batch->duration_in_days ?? 'N/A' }} days
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Attendance History --}}
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-clipboard-list mr-2"></i>Attendance History</h5>
                    <a href="{{ route('training.mark-attendance', $candidate) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-sm">
                        <i class="fas fa-plus mr-1"></i> Mark Attendance
                    </a>
                </div>
                <div class="p-5">
                    @if($candidate->attendances && $candidate->attendances->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($candidate->attendances->take(10) as $attendance)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">{{ $attendance->date ? $attendance->date->format('d M Y') : 'N/A' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                    {{ $attendance->status === 'present' ? 'bg-green-100 text-green-800' : ($attendance->status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                    {{ ucfirst($attendance->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $attendance->detailed_remarks ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($candidate->attendances->count() > 10)
                            <div class="text-center mt-3">
                                <span class="text-gray-500 text-sm">Showing 10 of {{ $candidate->attendances->count() }} records</span>
                            </div>
                        @endif
                    @else
                        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
                            <i class="fas fa-info-circle mr-2"></i>No attendance records found.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assessment History --}}
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-chart-line mr-2"></i>Assessment History</h5>
                    <a href="{{ route('training.assessment-view', $candidate) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-sm">
                        <i class="fas fa-plus mr-1"></i> Record Assessment
                    </a>
                </div>
                <div class="p-5">
                    @if($candidate->assessments && $candidate->assessments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Score</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Result</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($candidate->assessments as $assessment)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    {{ ucfirst($assessment->assessment_type) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">{{ $assessment->assessment_date ? $assessment->assessment_date->format('d M Y') : 'N/A' }}</td>
                                            <td class="px-4 py-3">
                                                <strong>{{ $assessment->total_score }}</strong> / {{ $assessment->max_score }}
                                                ({{ round(($assessment->total_score / $assessment->max_score) * 100) }}%)
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                    {{ $assessment->result === 'pass' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($assessment->result) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ Str::limit($assessment->remarks, 30) ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
                            <i class="fas fa-info-circle mr-2"></i>No assessment records found.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column - Sidebar --}}
        <div class="lg:col-span-4">
            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="bg-cyan-500 text-white px-5 py-3 border-b">
                    <h5 class="font-semibold"><i class="fas fa-bolt mr-2"></i>Quick Actions</h5>
                </div>
                <div class="p-5">
                    <div class="divide-y divide-gray-100">
                        <a href="{{ route('training.mark-attendance', $candidate) }}" class="py-3 px-0 flex items-center hover:bg-gray-50 -mx-5 px-5 transition-colors">
                            <i class="fas fa-clipboard-check text-green-600 mr-3"></i>
                            <div>
                                <strong class="text-gray-800">Mark Attendance</strong>
                                <small class="text-gray-500 block">Record today's attendance</small>
                            </div>
                        </a>
                        <a href="{{ route('training.assessment-view', $candidate) }}" class="py-3 px-0 flex items-center hover:bg-gray-50 -mx-5 px-5 transition-colors">
                            <i class="fas fa-chart-bar text-blue-600 mr-3"></i>
                            <div>
                                <strong class="text-gray-800">Conduct Assessment</strong>
                                <small class="text-gray-500 block">Record theory or practical exam</small>
                            </div>
                        </a>
                        @if($candidate->certificate)
                            <a href="{{ route('training.download-certificate', $candidate) }}" class="py-3 px-0 flex items-center hover:bg-gray-50 -mx-5 px-5 transition-colors">
                                <i class="fas fa-certificate text-yellow-500 mr-3"></i>
                                <div>
                                    <strong class="text-gray-800">Download Certificate</strong>
                                    <small class="text-gray-500 block">{{ $candidate->certificate->certificate_number }}</small>
                                </div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Certificate Status --}}
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-certificate mr-2"></i>Certificate Status</h5>
                </div>
                <div class="p-5">
                    @if($candidate->certificate)
                        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-3">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Certificate Issued</strong>
                        </div>
                        <p class="mb-1 text-sm"><strong>Certificate #:</strong> {{ $candidate->certificate->certificate_number }}</p>
                        <p class="mb-1 text-sm"><strong>Issue Date:</strong> {{ $candidate->certificate->issue_date ? $candidate->certificate->issue_date->format('d M Y') : 'N/A' }}</p>
                        <a href="{{ route('training.download-certificate', $candidate) }}" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm inline-flex items-center justify-center mt-3">
                            <i class="fas fa-download mr-1"></i>Download Certificate
                        </a>
                    @else
                        @php
                            $hasPassed = $candidate->assessments && $candidate->assessments->where('assessment_type', 'final')->where('result', 'pass')->count() > 0;
                        @endphp
                        @if($hasPassed)
                            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg mb-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                Eligible for certificate
                            </div>
                            <form action="{{ route('training.certificate', $candidate) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                    <i class="fas fa-certificate mr-1"></i>Generate Certificate
                                </button>
                            </form>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Not Eligible</strong><br>
                                <small>Must pass final assessment to receive certificate.</small>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Training Progress --}}
            <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-tasks mr-2"></i>Training Progress</h5>
                </div>
                <div class="p-5">
                    @php
                        $attendancePercentage = $attendanceStats['percentage'] ?? 0;
                        $hasInitialAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'initial')->count() > 0;
                        $hasMidtermAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'midterm')->count() > 0;
                        $hasPracticalAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'practical')->count() > 0;
                        $hasFinalAssessment = $candidate->assessments && $candidate->assessments->where('assessment_type', 'final')->count() > 0;
                    @endphp

                    <ul class="list-none space-y-3">
                        <li class="flex items-center justify-between">
                            <span>
                                <i class="fas {{ $attendancePercentage >= 80 ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600' }} mr-2"></i>
                                Attendance (80% required)
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $attendancePercentage >= 80 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $attendancePercentage }}%
                            </span>
                        </li>
                        <li>
                            <i class="fas {{ $hasInitialAssessment ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' }} mr-2"></i>
                            Initial Assessment
                        </li>
                        <li>
                            <i class="fas {{ $hasMidtermAssessment ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' }} mr-2"></i>
                            Midterm Assessment
                        </li>
                        <li>
                            <i class="fas {{ $hasPracticalAssessment ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' }} mr-2"></i>
                            Practical Assessment
                        </li>
                        <li>
                            <i class="fas {{ $hasFinalAssessment ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' }} mr-2"></i>
                            Final Assessment
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Complete Training --}}
            @if($candidate->status === 'training')
                <div class="bg-white rounded-xl shadow-sm border border-green-300 overflow-hidden">
                    <div class="bg-green-600 text-white px-5 py-3 border-b">
                        <h5 class="font-semibold"><i class="fas fa-graduation-cap mr-2"></i>Complete Training</h5>
                    </div>
                    <div class="p-5">
                        @php
                            $canComplete = $attendancePercentage >= 80 && $hasFinalAssessment && $candidate->assessments->where('assessment_type', 'final')->where('result', 'pass')->count() > 0;
                        @endphp

                        @if($canComplete)
                            <p class="text-gray-500 text-sm mb-3">All requirements met. Complete the training to move to the next stage.</p>
                            <form action="{{ route('training.complete', $candidate) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm" onclick="return confirm('Mark training as complete for this candidate?')">
                                    <i class="fas fa-check-double mr-1"></i>Complete Training
                                </button>
                            </form>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Cannot Complete</strong><br>
                                <small>
                                    Requirements:
                                    <ul class="list-none pl-3 mt-1 space-y-1">
                                        <li><i class="fas fa-circle text-xs mr-1"></i>Minimum 80% attendance</li>
                                        <li><i class="fas fa-circle text-xs mr-1"></i>Pass final assessment</li>
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
