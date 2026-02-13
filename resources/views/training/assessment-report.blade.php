@extends('layouts.app')
@section('title', 'Assessment Report')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Assessment Report</h2>
            <p class="text-gray-500 text-sm mt-1">Assessment performance analysis</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-print mr-1"></i>Print
            </button>
            <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    @if(isset($report['summary']))
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="text-blue-800">
                <p class="text-sm font-medium text-gray-500">Total Assessments</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $report['summary']['total'] ?? 0 }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="text-green-800">
                <p class="text-sm font-medium text-gray-500">Average Score</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($report['summary']['avg_score'] ?? 0, 1) }}%</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="text-purple-800">
                <p class="text-sm font-medium text-gray-500">Pass Rate</p>
                <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($report['summary']['pass_rate'] ?? 0, 1) }}%</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="text-orange-800">
                <p class="text-sm font-medium text-gray-500">Fail Rate</p>
                <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($report['summary']['fail_rate'] ?? 0, 1) }}%</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Results Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h5 class="font-semibold text-gray-800">Assessment Results</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Candidate</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Type</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Score</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Percentage</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Grade</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($report['assessments'] ?? $report['records'] ?? [] as $assessment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $assessment['candidate_name'] ?? $assessment['name'] ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                {{ ucfirst($assessment['assessment_type'] ?? $assessment['type'] ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">
                            {{ $assessment['score'] ?? $assessment['obtained_marks'] ?? 0 }}/{{ $assessment['max_score'] ?? $assessment['total_marks'] ?? 0 }}
                        </td>
                        <td class="px-4 py-3 text-center">{{ number_format($assessment['percentage'] ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $grade = $assessment['grade'] ?? 'N/A';
                                $gradeColor = match(true) {
                                    in_array($grade, ['A+', 'A']) => 'bg-green-100 text-green-800',
                                    $grade === 'B' => 'bg-blue-100 text-blue-800',
                                    $grade === 'C' => 'bg-yellow-100 text-yellow-800',
                                    $grade === 'D' => 'bg-orange-100 text-orange-800',
                                    $grade === 'F' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $gradeColor }}">
                                {{ $grade }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php $passed = $assessment['passed'] ?? ($assessment['result'] ?? '') === 'pass'; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $passed ? 'Pass' : 'Fail' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-chart-bar text-3xl text-gray-300 mb-2 block"></i>
                            No assessments found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
