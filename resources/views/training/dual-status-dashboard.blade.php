@extends('layouts.app')

@section('title', 'Dual Status Dashboard - ' . $batch->name)

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.index') }}" class="hover:text-blue-600">Training</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Dual Status</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-800">Dual Status Dashboard</h2>
            <p class="text-gray-500 text-sm">{{ $batch->name }} ({{ $batch->batch_code }})</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('training.batch-performance', $batch) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-chart-bar mr-1"></i> Performance Report
            </a>
            <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Candidates</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $summary['total_candidates'] }}</h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Technical Completed</p>
                    <h3 class="text-2xl font-bold text-green-600">{{ $summary['technical']['completed'] }}</h3>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-tools text-green-600"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center text-xs text-gray-500">
                <span class="text-yellow-500 mr-1">{{ $summary['technical']['in_progress'] }} in progress</span>
                <span class="text-gray-400">| {{ $summary['technical']['not_started'] }} pending</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Soft Skills Completed</p>
                    <h3 class="text-2xl font-bold text-purple-600">{{ $summary['soft_skills']['completed'] }}</h3>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-comments text-purple-600"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center text-xs text-gray-500">
                <span class="text-yellow-500 mr-1">{{ $summary['soft_skills']['in_progress'] }} in progress</span>
                <span class="text-gray-400">| {{ $summary['soft_skills']['not_started'] }} pending</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Fully Complete</p>
                    <h3 class="text-2xl font-bold text-emerald-600">{{ $summary['fully_complete'] }}</h3>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-emerald-600"></i>
                </div>
            </div>
            @if($summary['total_candidates'] > 0)
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ round(($summary['fully_complete'] / $summary['total_candidates']) * 100) }}%"></div>
            </div>
            @endif
        </div>
    </div>

    {{-- Dual Status Comparison Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h4 class="font-semibold text-gray-800 mb-4"><i class="fas fa-tools mr-2 text-blue-500"></i>Technical Training Progress</h4>
            <canvas id="technicalChart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h4 class="font-semibold text-gray-800 mb-4"><i class="fas fa-comments mr-2 text-purple-500"></i>Soft Skills Progress</h4>
            <canvas id="softSkillsChart" height="200"></canvas>
        </div>
    </div>

    {{-- Candidates Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-5 border-b">
            <h4 class="font-semibold text-gray-800"><i class="fas fa-list mr-2"></i>Candidate Training Status</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Candidate</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">TheLeap ID</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Technical</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Soft Skills</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Progress</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($trainings as $training)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $training->candidate->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $training->candidate->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $ts = $training->technical_training_status; @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $ts->value === 'completed' ? 'bg-green-100 text-green-800' : ($ts->value === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                                <i class="{{ $ts->icon() }} mr-1 text-xs"></i>{{ $ts->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php $ss = $training->soft_skills_status; @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $ss->value === 'completed' ? 'bg-green-100 text-green-800' : ($ss->value === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                                <i class="{{ $ss->icon() }} mr-1 text-xs"></i>{{ $ss->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $training->completion_percentage }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600">{{ $training->completion_percentage }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('training.candidate-progress', $training) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>No candidates in training for this batch.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const technicalData = {
        labels: ['Not Started', 'In Progress', 'Completed'],
        datasets: [{
            data: [{{ $summary['technical']['not_started'] }}, {{ $summary['technical']['in_progress'] }}, {{ $summary['technical']['completed'] }}],
            backgroundColor: ['#e5e7eb', '#fbbf24', '#10b981'],
            borderWidth: 0
        }]
    };

    const softSkillsData = {
        labels: ['Not Started', 'In Progress', 'Completed'],
        datasets: [{
            data: [{{ $summary['soft_skills']['not_started'] }}, {{ $summary['soft_skills']['in_progress'] }}, {{ $summary['soft_skills']['completed'] }}],
            backgroundColor: ['#e5e7eb', '#fbbf24', '#8b5cf6'],
            borderWidth: 0
        }]
    };

    const chartConfig = {
        type: 'doughnut',
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    };

    new Chart(document.getElementById('technicalChart'), { ...chartConfig, data: technicalData });
    new Chart(document.getElementById('softSkillsChart'), { ...chartConfig, data: softSkillsData });
});
</script>
@endpush
@endsection
