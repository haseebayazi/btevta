@extends('layouts.app')
@section('title', 'Trainer Detail - ' . $instructor->name)
@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <nav class="text-sm text-gray-600 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-2">/</span>
                <a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reports</a>
                <span class="mx-2">/</span>
                <a href="{{ route('reports.trainer-performance') }}" class="hover:text-blue-600">Trainer Performance</a>
                <span class="mx-2">/</span>
                <span>{{ $instructor->name }}</span>
            </nav>
            <h1 class="text-3xl font-bold">{{ $instructor->name }}</h1>
            <p class="text-gray-600 mt-1">
                {{ $instructor->trade->name ?? 'N/A' }} - {{ $instructor->campus->name ?? 'N/A' }}
            </p>
        </div>
        <a href="{{ route('reports.trainer-performance') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <!-- Trainer Info Card -->
    <div class="card mb-6">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Email</p>
                <p class="font-medium">{{ $instructor->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Phone</p>
                <p class="font-medium">{{ $instructor->phone ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Qualification</p>
                <p class="font-medium">{{ $instructor->qualification ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Experience</p>
                <p class="font-medium">{{ $instructor->experience_years ?? 0 }} years</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Employment Type</p>
                <p class="font-medium">{{ ucfirst($instructor->employment_type ?? 'N/A') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Joining Date</p>
                <p class="font-medium">{{ $instructor->joining_date ? $instructor->joining_date->format('d M Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Status</p>
                <span class="badge badge-{{ $instructor->status_badge_color }}">{{ ucfirst($instructor->status) }}</span>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Specialization</p>
                <p class="font-medium">{{ $instructor->specialization ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Performance Statistics -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <div class="card bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-800 mb-1">Total Batches</p>
                    <p class="text-3xl font-bold text-blue-900">{{ $stats['total_batches'] }}</p>
                </div>
                <i class="fas fa-layer-group text-blue-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-800 mb-1">Total Students</p>
                    <p class="text-3xl font-bold text-green-900">{{ $stats['total_students'] }}</p>
                </div>
                <i class="fas fa-users text-green-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-purple-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-800 mb-1">Pass Rate</p>
                    <p class="text-3xl font-bold text-purple-900">{{ $stats['pass_rate'] }}%</p>
                </div>
                <i class="fas fa-chart-line text-purple-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-indigo-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-indigo-800 mb-1">Attendance Rate</p>
                    <p class="text-3xl font-bold text-indigo-900">{{ $stats['attendance_rate'] }}%</p>
                </div>
                <i class="fas fa-clipboard-check text-indigo-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-yellow-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-800 mb-1">Average Score</p>
                    <p class="text-3xl font-bold text-yellow-900">{{ $stats['avg_score'] }}/100</p>
                </div>
                <i class="fas fa-star text-yellow-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-teal-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-teal-800 mb-1">Total Assessments</p>
                    <p class="text-3xl font-bold text-teal-900">{{ $stats['total_assessments'] }}</p>
                </div>
                <i class="fas fa-clipboard-list text-teal-400 text-4xl"></i>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <!-- Assessment Type Breakdown -->
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Assessment Type Performance</h2>
            @if($assessmentBreakdown->count() > 0)
                <div class="space-y-4">
                    @foreach($assessmentBreakdown as $item)
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="font-medium">{{ ucfirst($item->assessment_type) }}</span>
                                <span class="text-sm text-gray-600">{{ $item->total }} assessments</span>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center p-3 bg-blue-50 rounded">
                                    <p class="text-sm text-gray-600">Pass Rate</p>
                                    <p class="text-lg font-bold text-blue-900">{{ $item->pass_rate }}%</p>
                                </div>
                                <div class="text-center p-3 bg-green-50 rounded">
                                    <p class="text-sm text-gray-600">Passed</p>
                                    <p class="text-lg font-bold text-green-900">{{ $item->passed }}</p>
                                </div>
                                <div class="text-center p-3 bg-purple-50 rounded">
                                    <p class="text-sm text-gray-600">Avg Score</p>
                                    <p class="text-lg font-bold text-purple-900">{{ round($item->avg_score, 1) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No assessment data available</p>
            @endif
        </div>

        <!-- Monthly Performance Trend -->
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Performance Trend (Last 6 Months)</h2>
            @if($monthlyTrend->count() > 0)
                <canvas id="trendChart" height="300"></canvas>
            @else
                <p class="text-gray-500 text-center py-8">No trend data available</p>
            @endif
        </div>
    </div>

    <!-- Batches Taught -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Batches Taught</h2>
        @if($batches->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Batch Code</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Trade</th>
                            <th class="px-4 py-3 text-left">Campus</th>
                            <th class="px-4 py-3 text-center">Students</th>
                            <th class="px-4 py-3 text-center">Pass Rate</th>
                            <th class="px-4 py-3 text-center">Avg Score</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($batches as $batch)
                        <tr>
                            <td class="px-4 py-3 font-mono font-bold">{{ $batch->batch_code }}</td>
                            <td class="px-4 py-3">{{ $batch->name }}</td>
                            <td class="px-4 py-3">{{ $batch->trade->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $batch->campus->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center font-semibold">{{ $batch->student_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge badge-{{ $batch->pass_rate >= 70 ? 'success' : ($batch->pass_rate >= 50 ? 'warning' : 'danger') }}">
                                    {{ $batch->pass_rate }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold">{{ round($batch->avg_score, 1) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge badge-{{ $batch->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No batches found</p>
        @endif
    </div>

    <!-- Recent Assessments -->
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Recent Assessments</h2>
        @if($recentAssessments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Candidate</th>
                            <th class="px-4 py-3 text-left">Batch</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-center">Score</th>
                            <th class="px-4 py-3 text-center">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($recentAssessments as $assessment)
                        <tr>
                            <td class="px-4 py-3">{{ $assessment->assessment_date ? $assessment->assessment_date->format('d M Y') : 'N/A' }}</td>
                            <td class="px-4 py-3">
                                {{ $assessment->candidate->name ?? 'N/A' }}
                                <span class="text-xs text-gray-500 block">{{ $assessment->candidate->btevta_id ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $assessment->batch->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge badge-secondary">{{ ucfirst($assessment->assessment_type) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold">
                                {{ $assessment->total_score }}/{{ $assessment->max_score }}
                                <span class="text-xs text-gray-500">({{ round(($assessment->total_score/$assessment->max_score)*100, 1) }}%)</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge badge-{{ $assessment->result === 'pass' ? 'success' : 'danger' }}">
                                    {{ ucfirst($assessment->result) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No recent assessments</p>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
@if($monthlyTrend->count() > 0)
const trendData = @json($monthlyTrend);

// Monthly Trend Chart
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendData.map(d => {
            const [year, month] = d.month.split('-');
            return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Pass Rate (%)',
            data: trendData.map(d => d.pass_rate),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            yAxisID: 'y'
        }, {
            label: 'Avg Score',
            data: trendData.map(d => d.avg_score),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Pass Rate (%)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Average Score'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
@endif
</script>
@endpush
@endsection
