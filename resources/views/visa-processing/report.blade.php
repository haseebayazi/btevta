@extends('layouts.app')
@section('title', 'Visa Processing Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Visa Processing Report</h1>
        <div class="flex gap-2">
            <button onclick="exportPDF()" class="btn btn-danger">
                <i class="fas fa-file-pdf mr-2"></i>Export PDF
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print mr-2"></i>Print
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Campus</label>
                <select name="campus_id" class="form-input">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Generate Report</button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Processes</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Completed</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['completed'] }}</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">In Progress</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Overdue</p>
            <p class="text-3xl font-bold text-red-900">{{ $stats['overdue'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Avg Duration</p>
            <p class="text-3xl font-bold text-purple-900">{{ $stats['avg_duration'] }}d</p>
        </div>
    </div>

    <!-- Stage Statistics -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Processing by Stage</h2>
        <div class="grid md:grid-cols-4 gap-4">
            @foreach($stageStats as $stage)
            <div class="p-4 border rounded-lg">
                <p class="text-sm text-gray-600">Stage {{ $stage->stage_number }}</p>
                <p class="text-2xl font-bold">{{ $stage->count }}</p>
                <div class="mt-2 bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 rounded-full h-2" style="width: {{ $stage->percentage }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Processing Time Chart -->
    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Average Processing Time by Stage</h2>
            <canvas id="processingTimeChart" height="300"></canvas>
        </div>
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Monthly Completion Trend</h2>
            <canvas id="completionTrendChart" height="300"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const processingData = @json($processingTimeData);
const completionData = @json($completionTrendData);

new Chart(document.getElementById('processingTimeChart'), {
    type: 'bar',
    data: {
        labels: processingData.labels,
        datasets: [{
            label: 'Days',
            data: processingData.values,
            backgroundColor: 'rgb(59, 130, 246)'
        }]
    }
});

new Chart(document.getElementById('completionTrendChart'), {
    type: 'line',
    data: {
        labels: completionData.labels,
        datasets: [{
            label: 'Completions',
            data: completionData.values,
            borderColor: 'rgb(34, 197, 94)',
            tension: 0.4
        }]
    }
});

function exportPDF() {
    alert('PDF export will be implemented');
}
</script>
@endpush
@endsection