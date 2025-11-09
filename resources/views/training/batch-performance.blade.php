@extends('layouts.app')
@section('title', 'Batch Performance - ' . $training->batch_name)
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Batch Performance Analysis</h1>
        <p class="text-gray-600 mt-1">{{ $training->title }} - {{ $training->batch_name }}</p>
    </div>
    
    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <div class="card bg-blue-50">
            <h3 class="text-sm font-medium text-blue-800 mb-2">Average Attendance</h3>
            <p class="text-3xl font-bold text-blue-900">{{ number_format($avgAttendance, 1) }}%</p>
        </div>
        <div class="card bg-green-50">
            <h3 class="text-sm font-medium text-green-800 mb-2">Average Score</h3>
            <p class="text-3xl font-bold text-green-900">{{ number_format($avgScore, 1) }}%</p>
        </div>
        <div class="card bg-purple-50">
            <h3 class="text-sm font-medium text-purple-800 mb-2">Pass Rate</h3>
            <p class="text-3xl font-bold text-purple-900">{{ number_format($passRate, 1) }}%</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Attendance Trend</h2>
            <canvas id="attendanceChart" height="300"></canvas>
        </div>
        <div class="card">
            <h2 class="text-xl font-bold mb-4">Assessment Performance</h2>
            <canvas id="assessmentChart" height="300"></canvas>
        </div>
    </div>

    <div class="card mt-6">
        <h2 class="text-xl font-bold mb-4">Top Performers</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Rank</th>
                        <th class="px-4 py-3 text-left">Candidate</th>
                        <th class="px-4 py-3 text-center">Attendance</th>
                        <th class="px-4 py-3 text-center">Avg Score</th>
                        <th class="px-4 py-3 text-center">Grade</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($topPerformers as $index => $performer)
                    <tr>
                        <td class="px-4 py-3">
                            @if($index < 3)
                                <i class="fas fa-medal text-{{ ['yellow', 'gray', 'orange'][$index] }}-500 text-xl"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $performer->name }}</td>
                        <td class="px-4 py-3 text-center">{{ number_format($performer->attendance_percentage, 1) }}%</td>
                        <td class="px-4 py-3 text-center">{{ number_format($performer->avg_score, 1) }}%</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-success">{{ $performer->grade }}</span>
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
const attendanceData = @json($attendanceData);
const assessmentData = @json($assessmentData);

// Attendance Chart
new Chart(document.getElementById('attendanceChart'), {
    type: 'line',
    data: {
        labels: attendanceData.labels,
        datasets: [{
            label: 'Attendance %',
            data: attendanceData.values,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

// Assessment Chart
new Chart(document.getElementById('assessmentChart'), {
    type: 'bar',
    data: {
        labels: assessmentData.labels,
        datasets: [{
            label: 'Average Score',
            data: assessmentData.values,
            backgroundColor: 'rgb(34, 197, 94)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});
</script>
@endpush
@endsection