@extends('layouts.app')
@section('title', 'Visa Timeline Report')
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <h2 class="text-3xl font-bold">Visa Processing Timeline Report</h2>
        <p class="text-gray-600 mt-2">Comprehensive analysis of visa processing timelines and stages</p>
    </div>

    @if(isset($data) && $data)
    <!-- Summary Cards -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50 p-6">
            <p class="text-sm text-blue-800 mb-2">Average Processing Days</p>
            <p class="text-3xl font-bold text-blue-900">{{ round($data->avg_days ?? 0) }}</p>
            <p class="text-xs text-blue-700 mt-1">days</p>
        </div>
        <div class="card bg-green-50 p-6">
            <p class="text-sm text-green-800 mb-2">Fastest Processing</p>
            <p class="text-3xl font-bold text-green-900">{{ round($data->min_days ?? 0) }}</p>
            <p class="text-xs text-green-700 mt-1">days</p>
        </div>
        <div class="card bg-yellow-50 p-6">
            <p class="text-sm text-yellow-800 mb-2">Slowest Processing</p>
            <p class="text-3xl font-bold text-yellow-900">{{ round($data->max_days ?? 0) }}</p>
            <p class="text-xs text-yellow-700 mt-1">days</p>
        </div>
        <div class="card bg-purple-50 p-6">
            <p class="text-sm text-purple-800 mb-2">Total Applications</p>
            <p class="text-3xl font-bold text-purple-900">{{ $data->total ?? 0 }}</p>
            <p class="text-xs text-purple-700 mt-1">processed</p>
        </div>
    </div>

    <!-- Processing Stages Breakdown -->
    @if(isset($data->stage_breakdown) && count($data->stage_breakdown) > 0)
    <div class="card p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">Processing Time by Stage</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($data->stage_breakdown as $stage)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ ucwords(str_replace('_', ' ', $stage->stage_name)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ round($stage->avg_days) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ round($stage->min_days) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ round($stage->max_days) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $stage->count }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Timeline Chart -->
    <div class="card p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">Processing Timeline Distribution</h3>
        <canvas id="timelineChart" height="80"></canvas>
    </div>

    <!-- Status Breakdown -->
    @if(isset($data->by_status) && count($data->by_status) > 0)
    <div class="card p-6">
        <h3 class="text-xl font-bold mb-4">Applications by Status</h3>
        <div class="grid md:grid-cols-3 gap-4">
            @foreach($data->by_status as $status)
            <div class="border rounded-lg p-4">
                <p class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $status->status)) }}</p>
                <p class="text-2xl font-bold text-gray-900">{{ $status->count }}</p>
                <p class="text-xs text-gray-500">{{ round(($status->count / $data->total) * 100, 1) }}% of total</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @else
    <div class="card p-6">
        <div class="text-center text-gray-500 py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No data available</h3>
            <p class="mt-1 text-sm text-gray-500">There are no visa processing records to display.</p>
        </div>
    </div>
    @endif
</div>
@endsection

@if(isset($data) && $data)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('timelineChart');

        @if(isset($data->timeline_distribution) && count($data->timeline_distribution) > 0)
        const data = {
            labels: [
                @foreach($data->timeline_distribution as $item)
                    '{{ $item->range }}',
                @endforeach
            ],
            datasets: [{
                label: 'Number of Applications',
                data: [
                    @foreach($data->timeline_distribution as $item)
                        {{ $item->count }},
                    @endforeach
                ],
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Distribution of Processing Times'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
        @else
        // Fallback: Simple bar chart with average days
        const data = {
            labels: ['Average Processing Time'],
            datasets: [{
                label: 'Days',
                data: [{{ round($data->avg_days ?? 0) }}],
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        new Chart(ctx, config);
        @endif
    });
</script>
@endpush
@endif
