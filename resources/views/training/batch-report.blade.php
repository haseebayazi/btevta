@extends('layouts.app')
@section('title', 'Batch Report')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Batch {{ $batch->batch_number }} Report</h2>
            <p class="text-gray-500 text-sm mt-1">Training performance summary</p>
        </div>
        <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Candidate</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Attendance %</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Pass/Fail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($batch->candidates as $candidate)
                    @php
                        // Use eager-loaded collections to avoid N+1 queries
                        $attendances = $candidate->relationLoaded('attendances') ? $candidate->attendances : collect();
                        $present = $attendances->where('status', 'present')->count();
                        $total = $attendances->count();
                        $pct = $total > 0 ? round(($present / $total) * 100) : 0;

                        $assessments = $candidate->relationLoaded('assessments') ? $candidate->assessments : collect();
                        $passed = $assessments->where('result', 'pass')->count() > 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $candidate->name }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pct >= 80 ? 'bg-green-100 text-green-800' : ($pct >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $pct }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $passed ? 'PASS' : 'FAIL' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
