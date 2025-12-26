@extends('layouts.app')

@section('title', 'Compliance Monitoring - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Compliance Monitoring Dashboard</h1>
            <p class="text-gray-600 mt-1">Track compliance rates across all operations</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Overall Score Card -->
    <div class="bg-gradient-to-r {{ $overallScore['score'] >= 80 ? 'from-green-600 to-green-800' : ($overallScore['score'] >= 60 ? 'from-yellow-600 to-yellow-800' : 'from-red-600 to-red-800') }} rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold opacity-90">Overall Compliance Score</h2>
                <div class="flex items-baseline mt-2">
                    <span class="text-6xl font-bold">{{ $overallScore['score'] }}%</span>
                    <span class="text-2xl font-semibold ml-3 opacity-90">Grade: {{ $overallScore['grade'] }}</span>
                </div>
                <p class="mt-2 opacity-80">Status: {{ $overallScore['status'] }}</p>
            </div>
            <div class="text-right hidden md:block">
                <div class="text-6xl opacity-30">
                    @if($overallScore['score'] >= 80)
                        <i class="fas fa-check-circle"></i>
                    @elseif($overallScore['score'] >= 60)
                        <i class="fas fa-exclamation-circle"></i>
                    @else
                        <i class="fas fa-times-circle"></i>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Compliance Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Document Compliance -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>Document Compliance
                </h3>
                <span class="text-2xl font-bold {{ $documentCompliance['rate'] >= 80 ? 'text-green-600' : ($documentCompliance['rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $documentCompliance['rate'] }}%
                </span>
            </div>

            <div class="mb-4">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="{{ $documentCompliance['rate'] >= 80 ? 'bg-green-600' : ($documentCompliance['rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-600') }} rounded-full h-3 transition-all"
                         style="width: {{ $documentCompliance['rate'] }}%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Total Candidates</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($documentCompliance['total']) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Complete Docs</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($documentCompliance['complete']) }}</p>
                </div>
                <div class="bg-red-50 rounded p-3">
                    <p class="text-xs text-red-600 uppercase">Expired</p>
                    <p class="text-xl font-bold text-red-700">{{ number_format($documentCompliance['expired']) }}</p>
                </div>
                <div class="bg-yellow-50 rounded p-3">
                    <p class="text-xs text-yellow-600 uppercase">Expiring Soon</p>
                    <p class="text-xl font-bold text-yellow-700">{{ number_format($documentCompliance['expiring_soon']) }}</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('document-archive.reports.missing') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Missing Documents Report <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Training Compliance -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-graduation-cap text-green-600 mr-2"></i>Training Compliance
                </h3>
                <span class="text-2xl font-bold {{ $trainingCompliance['attendance_rate'] >= 80 ? 'text-green-600' : ($trainingCompliance['attendance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $trainingCompliance['attendance_rate'] }}%
                </span>
            </div>

            <div class="mb-4">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="{{ $trainingCompliance['attendance_rate'] >= 80 ? 'bg-green-600' : ($trainingCompliance['attendance_rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-600') }} rounded-full h-3 transition-all"
                         style="width: {{ $trainingCompliance['attendance_rate'] }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Attendance Rate (This Month)</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Total Sessions</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($trainingCompliance['total_sessions']) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Present</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($trainingCompliance['present_count']) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Active Batches</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($trainingCompliance['active_batches']) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Schedule Rate</p>
                    <p class="text-xl font-bold text-blue-600">{{ $trainingCompliance['schedule_rate'] }}%</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('reports.trainer-performance') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Trainer Performance <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Departure Compliance (90-Day) -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-plane-departure text-purple-600 mr-2"></i>90-Day Compliance
                </h3>
                <span class="text-2xl font-bold {{ $departureCompliance['rate'] >= 80 ? 'text-green-600' : ($departureCompliance['rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $departureCompliance['rate'] }}%
                </span>
            </div>

            <div class="mb-4">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="{{ $departureCompliance['rate'] >= 80 ? 'bg-green-600' : ($departureCompliance['rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-600') }} rounded-full h-3 transition-all"
                         style="width: {{ $departureCompliance['rate'] }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">90-Day Report Submission Rate</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Total (90+ days)</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($departureCompliance['total']) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Compliant</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($departureCompliance['compliant']) }}</p>
                </div>
                <div class="bg-red-50 rounded p-3">
                    <p class="text-xs text-red-600 uppercase">Overdue</p>
                    <p class="text-xl font-bold text-red-700">{{ number_format($departureCompliance['overdue']) }}</p>
                </div>
                <div class="bg-yellow-50 rounded p-3">
                    <p class="text-xs text-yellow-600 uppercase">Due Soon</p>
                    <p class="text-xl font-bold text-yellow-700">{{ number_format($departureCompliance['due_soon']) }}</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('reports.departure-updates') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Departure Updates <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Complaint SLA Compliance -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>Complaint SLA
                </h3>
                <span class="text-2xl font-bold {{ $complaintCompliance['sla_rate'] >= 80 ? 'text-green-600' : ($complaintCompliance['sla_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $complaintCompliance['sla_rate'] }}%
                </span>
            </div>

            <div class="mb-4">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="{{ $complaintCompliance['sla_rate'] >= 80 ? 'bg-green-600' : ($complaintCompliance['sla_rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-600') }} rounded-full h-3 transition-all"
                         style="width: {{ $complaintCompliance['sla_rate'] }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Resolved Within SLA</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Total Resolved</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($complaintCompliance['total_resolved']) }}</p>
                </div>
                <div class="bg-gray-50 rounded p-3">
                    <p class="text-xs text-gray-500 uppercase">Within SLA</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($complaintCompliance['within_sla']) }}</p>
                </div>
                <div class="bg-red-50 rounded p-3">
                    <p class="text-xs text-red-600 uppercase">Currently Overdue</p>
                    <p class="text-xl font-bold text-red-700">{{ number_format($complaintCompliance['current_overdue']) }}</p>
                </div>
                <div class="bg-blue-50 rounded p-3">
                    <p class="text-xs text-blue-600 uppercase">Avg Resolution</p>
                    <p class="text-xl font-bold text-blue-700">{{ $complaintCompliance['avg_resolution_days'] }} days</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('complaints.sla-report') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View SLA Report <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- Summary Actions -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('document-archive.expiring') }}" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                <i class="fas fa-file-exclamation text-blue-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold text-gray-900">Expiring Documents</p>
                    <p class="text-sm text-gray-600">Review & renew</p>
                </div>
            </a>
            <a href="{{ route('complaints.overdue') }}" class="flex items-center p-4 bg-red-50 hover:bg-red-100 rounded-lg transition">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold text-gray-900">Overdue Complaints</p>
                    <p class="text-sm text-gray-600">Urgent attention needed</p>
                </div>
            </a>
            <a href="{{ route('departure.reports.pending-activations') }}" class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                <i class="fas fa-id-card text-purple-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold text-gray-900">Pending Activations</p>
                    <p class="text-sm text-gray-600">Iqama/Absher status</p>
                </div>
            </a>
            <a href="{{ route('reports.index') }}" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition">
                <i class="fas fa-chart-bar text-green-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-semibold text-gray-900">All Reports</p>
                    <p class="text-sm text-gray-600">Full reporting suite</p>
                </div>
            </a>
        </div>
    </div>

</div>
@endsection
