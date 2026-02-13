@extends('layouts.app')

@section('title', 'Training Management')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Training Management</h2>
            <p class="text-gray-500 text-sm mt-1">Manage candidates currently in training</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('training.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i> New Training
            </a>
            <a href="{{ route('admin.batches.index') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-list mr-1"></i> Batches
            </a>
        </div>
    </div>

    {{-- Module 4: Dual-Status Dashboard Notice --}}
    @if($batches->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden">
        <div class="bg-blue-600 text-white px-5 py-3">
            <h5 class="font-semibold"><i class="fas fa-chart-line mr-2"></i>Dual-Status Training Dashboard</h5>
        </div>
        <div class="p-5">
            <p class="text-gray-600 mb-4">Track Technical and Soft Skills training separately for enhanced progress monitoring.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($batches as $batch)
                <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <h6 class="font-semibold text-gray-800">{{ $batch->name }}</h6>
                    <p class="text-gray-500 text-sm mb-3">{{ $batch->batch_code }}</p>
                    <a href="{{ route('training.dual-status-dashboard', $batch) }}" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg">
                        <i class="fas fa-chart-bar mr-1"></i> View Dashboard
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Candidates Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h5 class="font-semibold text-gray-800">Candidates in Training</h5>
        </div>
        <div class="p-5">
            @if($candidates->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">TheLeap ID</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Trade</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Campus</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Batch</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Attendance</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($candidates as $candidate)
                                @php
                                    // Use eager-loaded attendances collection to avoid N+1 queries
                                    $totalAttendances = $candidate->attendances->count();
                                    $presentCount = $candidate->attendances->where('status', 'present')->count();
                                    $attendancePct = $totalAttendances > 0 ? round(($presentCount / $totalAttendances) * 100) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $candidate->btevta_id }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $candidate->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $candidate->trade?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $candidate->campus?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $candidate->batch?->batch_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $attendancePct >= 80 ? 'bg-green-100 text-green-800' : ($attendancePct >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $attendancePct }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            <a href="{{ route('training.show', $candidate) }}" class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 p-1.5 rounded" title="View">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <a href="{{ route('training.edit', $candidate) }}" class="bg-yellow-50 text-yellow-600 hover:bg-yellow-100 p-1.5 rounded" title="Edit">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <form action="{{ route('training.destroy', $candidate) }}" method="POST" class="inline" data-confirm="Remove this candidate from training?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="bg-red-50 text-red-600 hover:bg-red-100 p-1.5 rounded" title="Remove" onclick="return confirm('Remove from training?')">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $candidates->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-graduation-cap text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No candidates in training.</p>
                    <a href="{{ route('training.create') }}" class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-block">Start now</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
