@extends('layouts.app')
@section('title', 'Assessment Analytics')
@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Assessment Analytics</h1>
            <p class="text-gray-600 mt-1">Comprehensive analysis of training assessments</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Reports
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="form-label">Campus</label>
                <select name="campus_id" class="form-input">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['campus_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Batch</label>
                <select name="batch_id" class="form-input">
                    <option value="">All Batches</option>
                    @foreach($batches as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['batch_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Assessment Type</label>
                <select name="assessment_type" class="form-input">
                    <option value="">All Types</option>
                    <option value="initial" {{ ($validated['assessment_type'] ?? '') == 'initial' ? 'selected' : '' }}>Initial</option>
                    <option value="midterm" {{ ($validated['assessment_type'] ?? '') == 'midterm' ? 'selected' : '' }}>Midterm</option>
                    <option value="practical" {{ ($validated['assessment_type'] ?? '') == 'practical' ? 'selected' : '' }}>Practical</option>
                    <option value="final" {{ ($validated['assessment_type'] ?? '') == 'final' ? 'selected' : '' }}>Final</option>
                </select>
            </div>
            <div>
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-input" value="{{ $validated['from_date'] ?? '' }}">
            </div>
            <div>
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-input" value="{{ $validated['to_date'] ?? '' }}">
            </div>
            <div class="flex items-end col-span-full">
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                <a href="{{ route('reports.assessment-analytics') }}" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Overall Statistics -->
    <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
        <div class="card bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-800 mb-1">Total Assessments</p>
                    <p class="text-3xl font-bold text-blue-900">{{ number_format($stats['total_assessments']) }}</p>
                </div>
                <i class="fas fa-clipboard-list text-blue-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-800 mb-1">Passed</p>
                    <p class="text-3xl font-bold text-green-900">{{ number_format($stats['passed']) }}</p>
                </div>
                <i class="fas fa-check-circle text-green-400 text-4xl"></i>
            </div>
        </div>
        <div class="card bg-red-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-800 mb-1">Failed</p>
                    <p class="text-3xl font-bold text-red-900">{{ number_format($stats['failed']) }}</p>
                </div>
                <i class="fas fa-times-circle text-red-400 text-4xl"></i>
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
        <div class="card bg-yellow-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-800 mb-1">Average Score</p>
                    <p class="text-3xl font-bold text-yellow-900">{{ $stats['average_score'] }}</p>
                </div>
                <i class="fas fa-star text-yellow-400 text-4xl"></i>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <!-- Performance by Assessment Type -->
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Performance by Assessment Type</h2>
            @if($byType->count() > 0)
                <canvas id="typeChart" height="300"></canvas>
            @else
                <p class="text-gray-500 text-center py-8">No data available</p>
            @endif
        </div>

        <!-- Score Distribution -->
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Score Distribution</h2>
            @if($scoreDistribution->count() > 0)
                <canvas id="distributionChart" height="300"></canvas>
            @else
                <p class="text-gray-500 text-center py-8">No data available</p>
            @endif
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Performance Trend (Last 12 Months)</h2>
        @if($monthlyTrend->count() > 0)
            <canvas id="trendChart" height="120"></canvas>
        @else
            <p class="text-gray-500 text-center py-8">No trend data available</p>
        @endif
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <!-- Performance by Campus -->
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Performance by Campus</h2>
            @if($byCampus->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Campus</th>
                                <th class="px-4 py-3 text-center">Total</th>
                                <th class="px-4 py-3 text-center">Passed</th>
                                <th class="px-4 py-3 text-center">Pass Rate</th>
                                <th class="px-4 py-3 text-center">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($byCampus as $campus)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $campus['campus_name'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $campus['total'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $campus['passed'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge badge-{{ $campus['pass_rate'] >= 70 ? 'success' : ($campus['pass_rate'] >= 50 ? 'warning' : 'danger') }}">
                                        {{ $campus['pass_rate'] }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold">{{ $campus['avg_score'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No campus data available</p>
            @endif
        </div>

        <!-- Top 10 Batches by Pass Rate -->
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Top 10 Batches by Pass Rate</h2>
            @if($byBatch->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Batch</th>
                                <th class="px-4 py-3 text-center">Total</th>
                                <th class="px-4 py-3 text-center">Pass Rate</th>
                                <th class="px-4 py-3 text-center">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($byBatch as $batch)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-medium">{{ $batch->batch_name }}</span>
                                    <span class="text-xs text-gray-500 block">{{ $batch->batch_code }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">{{ $batch->total }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge badge-{{ $batch->pass_rate >= 70 ? 'success' : ($batch->pass_rate >= 50 ? 'warning' : 'danger') }}">
                                        {{ $batch->pass_rate }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold">{{ round($batch->avg_score, 1) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No batch data available</p>
            @endif
        </div>
    </div>

    <!-- Top 10 Performers -->
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Top 10 Performers</h2>
        @if($topPerformers->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Rank</th>
                            <th class="px-4 py-3 text-left">TheLeap ID</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Campus</th>
                            <th class="px-4 py-3 text-left">Trade</th>
                            <th class="px-4 py-3 text-center">Assessments</th>
                            <th class="px-4 py-3 text-center">Passed</th>
                            <th class="px-4 py-3 text-center">Average Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($topPerformers as $index => $performer)
                        <tr class="{{ $index < 3 ? 'bg-yellow-50' : '' }}">
                            <td class="px-4 py-3">
                                @if($index < 3)
                                    <i class="fas fa-medal text-{{ ['yellow', 'gray', 'orange'][$index] }}-500 text-xl"></i>
                                @else
                                    <span class="font-semibold">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-sm">{{ $performer->btevta_id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $performer->name }}</td>
                            <td class="px-4 py-3">{{ $performer->campus->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $performer->trade->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">{{ $performer->total_assessments }}</td>
                            <td class="px-4 py-3 text-center">{{ $performer->passed_assessments }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-lg text-green-600">{{ round($performer->avg_score, 1) }}%</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No top performers data available</p>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart colors
const colors = {
    blue: 'rgb(59, 130, 246)',
    green: 'rgb(34, 197, 94)',
    red: 'rgb(239, 68, 68)',
    yellow: 'rgb(234, 179, 8)',
    purple: 'rgb(168, 85, 247)',
    indigo: 'rgb(99, 102, 241)'
};

@if($byType->count() > 0)
// Assessment Type Chart
const typeData = @json($byType);
new Chart(document.getElementById('typeChart'), {
    type: 'bar',
    data: {
        labels: typeData.map(d => d.assessment_type.charAt(0).toUpperCase() + d.assessment_type.slice(1)),
        datasets: [{
            label: 'Total',
            data: typeData.map(d => d.total),
            backgroundColor: colors.blue,
            yAxisID: 'y'
        }, {
            label: 'Pass Rate (%)',
            data: typeData.map(d => d.pass_rate),
            backgroundColor: colors.green,
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
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: { display: true, text: 'Total Assessments' }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: { display: true, text: 'Pass Rate (%)' },
                grid: { drawOnChartArea: false },
                max: 100
            }
        }
    }
});
@endif

@if($scoreDistribution->count() > 0)
// Score Distribution Chart
const distributionData = @json($scoreDistribution);
new Chart(document.getElementById('distributionChart'), {
    type: 'doughnut',
    data: {
        labels: distributionData.map(d => d.score_range),
        datasets: [{
            data: distributionData.map(d => d.count),
            backgroundColor: [
                colors.green,
                colors.blue,
                colors.indigo,
                colors.yellow,
                colors.purple,
                colors.red
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});
@endif

@if($monthlyTrend->count() > 0)
// Monthly Trend Chart
const trendData = @json($monthlyTrend);
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
            borderColor: colors.green,
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Average Score',
            data: trendData.map(d => d.avg_score),
            borderColor: colors.blue,
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
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
            legend: { position: 'top' }
        }
    }
});
@endif
</script>
@endpush
@endsection
