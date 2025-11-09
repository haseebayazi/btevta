@extends('layouts.app')
@section('title', 'Complaints Analytics')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Complaints Analytics</h1>
        <button onclick="exportReport()" class="btn btn-success">
            <i class="fas fa-download mr-2"></i>Export Report
        </button>
    </div>

    <!-- Key Metrics -->
    <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Complaints</p>
            <p class="text-3xl font-bold text-blue-900">{{ $metrics['total'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Resolved</p>
            <p class="text-3xl font-bold text-green-900">{{ $metrics['resolved'] }}</p>
            <p class="text-xs text-green-700 mt-1">{{ $metrics['resolved_percentage'] }}%</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">Pending</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $metrics['pending'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Avg Resolution Time</p>
            <p class="text-3xl font-bold text-purple-900">{{ $metrics['avg_resolution'] }}h</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">SLA Breaches</p>
            <p class="text-3xl font-bold text-red-900">{{ $metrics['sla_breaches'] }}</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Complaints by Category</h3>
            <canvas id="categoryChart" height="300"></canvas>
        </div>
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Monthly Trend</h3>
            <canvas id="trendChart" height="300"></canvas>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Resolution Time Distribution</h3>
            <canvas id="resolutionChart" height="300"></canvas>
        </div>
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Priority Distribution</h3>
            <canvas id="priorityChart" height="300"></canvas>
        </div>
    </div>

    <!-- Top Categories Table -->
    <div class="card mt-6">
        <h3 class="text-lg font-bold mb-4">Top Categories This Month</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-center">Count</th>
                        <th class="px-4 py-3 text-center">Resolved</th>
                        <th class="px-4 py-3 text-center">Avg Time (hours)</th>
                        <th class="px-4 py-3 text-center">SLA Compliance</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($topCategories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-center">{{ $category->count }}</td>
                        <td class="px-4 py-3 text-center text-green-600">{{ $category->resolved }}</td>
                        <td class="px-4 py-3 text-center">{{ number_format($category->avg_time, 1) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $category->sla_compliance >= 90 ? 'success' : 'warning' }}">
                                {{ number_format($category->sla_compliance, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = @json($chartData);

new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: chartData.categories.labels,
        datasets: [{
            data: chartData.categories.values,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    }
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: chartData.trend.labels,
        datasets: [{
            label: 'Complaints',
            data: chartData.trend.values,
            borderColor: '#3b82f6',
            tension: 0.4
        }]
    }
});

new Chart(document.getElementById('resolutionChart'), {
    type: 'bar',
    data: {
        labels: chartData.resolution.labels,
        datasets: [{
            label: 'Count',
            data: chartData.resolution.values,
            backgroundColor: '#10b981'
        }]
    }
});

new Chart(document.getElementById('priorityChart'), {
    type: 'doughnut',
    data: {
        labels: chartData.priority.labels,
        datasets: [{
            data: chartData.priority.values,
            backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6']
        }]
    }
});
</script>
@endpush
@endsection