@extends('layouts.app')
@section('title', 'Batch Performance - ' . $batch->name)
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Batch Performance Analysis</h2>
            <p class="text-gray-500 text-sm mt-1">{{ $batch->name }} - {{ $batch->batch_code }}</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Average Attendance</h3>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($performance['avg_attendance'] ?? 0, 1) }}%</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Average Score</h3>
            <p class="text-3xl font-bold text-green-600">{{ number_format($performance['avg_score'] ?? 0, 1) }}%</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Pass Rate</h3>
            <p class="text-3xl font-bold text-purple-600">{{ number_format($performance['pass_rate'] ?? 0, 1) }}%</p>
        </div>
    </div>

    {{-- Charts --}}
    @if(isset($performance['attendance_data']) && isset($performance['assessment_data']))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h4 class="text-lg font-bold text-gray-800 mb-4">Attendance Trend</h4>
            <canvas id="attendanceChart" height="300"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h4 class="text-lg font-bold text-gray-800 mb-4">Assessment Performance</h4>
            <canvas id="assessmentChart" height="300"></canvas>
        </div>
    </div>
    @endif

    {{-- Top Performers --}}
    @if(isset($performance['top_performers']))
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h4 class="font-semibold text-gray-800">Top Performers</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Rank</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Candidate</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Attendance</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Avg Score</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Grade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($performance['top_performers'] as $index => $performer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @if($index < 3)
                                <i class="fas fa-medal text-{{ ['yellow', 'gray', 'orange'][$index] }}-500 text-xl"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $performer['name'] ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ number_format($performer['attendance_percentage'] ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-center">{{ number_format($performer['avg_score'] ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $performer['grade'] ?? 'N/A' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if(isset($performance['attendance_data']) && isset($performance['assessment_data']))
@push('scripts')
<script>
const attendanceData = @json($performance['attendance_data']);
const assessmentData = @json($performance['assessment_data']);

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
@endif
@endsection
