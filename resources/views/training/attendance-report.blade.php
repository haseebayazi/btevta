@extends('layouts.app')
@section('title', 'Attendance Report')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Attendance Report</h2>
            <p class="text-gray-500 text-sm mt-1">{{ $batch->name ?? 'All Batches' }} - Attendance Summary</p>
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
            <p class="text-sm text-gray-500">Total Sessions</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $report['summary']['total_sessions'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <p class="text-sm text-gray-500">Avg Attendance</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($report['summary']['avg_attendance'] ?? 0, 1) }}%</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <p class="text-sm text-gray-500">Total Candidates</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $report['summary']['total_candidates'] ?? $batch->candidates_count ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <p class="text-sm text-gray-500">Batch</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $batch->batch_code ?? 'N/A' }}</p>
        </div>
    </div>
    @endif

    {{-- Report Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h5 class="font-semibold text-gray-800">Attendance Records</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Candidate</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Present</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Absent</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Leave</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Total</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Attendance %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($report['records'] ?? $report['candidates'] ?? [] as $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $record['name'] ?? $record['candidate_name'] ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $record['present'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-center text-red-600 font-semibold">{{ $record['absent'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-center text-yellow-600 font-semibold">{{ $record['leave'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-center text-gray-800">{{ $record['total'] ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $pct = $record['percentage'] ?? $record['attendance_percentage'] ?? 0; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pct >= 80 ? 'bg-green-100 text-green-800' : ($pct >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ number_format($pct, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-3xl text-gray-300 mb-2 block"></i>
                            No attendance records found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
