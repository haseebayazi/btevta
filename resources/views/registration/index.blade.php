@extends('layouts.app')
@section('title', 'Registration Management')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Registration Management</h2>
            <p class="text-gray-500 text-sm mt-1">Manage candidate registrations and allocation</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-blue-600 text-white rounded-lg shadow-sm p-6">
            <h5 class="text-sm font-medium opacity-90">Pending Registration</h5>
            <p class="text-3xl font-bold mt-2">{{ $candidates->total() }}</p>
            <p class="text-xs opacity-75 mt-1">Screened candidates awaiting allocation</p>
        </div>
        <div class="bg-green-600 text-white rounded-lg shadow-sm p-6">
            <h5 class="text-sm font-medium opacity-90">Registered</h5>
            <p class="text-3xl font-bold mt-2">{{ $registeredCandidates->total() }}</p>
            <p class="text-xs opacity-75 mt-1">Candidates registered and allocated</p>
        </div>
    </div>

    {{-- Pending Registration Table --}}
    @if($candidates->count())
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">Candidates Pending Registration</h5>
                <p class="text-xs text-gray-500 mt-1">Screened candidates ready for allocation and registration</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">TheLeap ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">CNIC</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($candidates as $candidate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-semibold text-gray-900">{{ $candidate->btevta_id }}</td>
                                <td class="px-6 py-4 text-gray-900">{{ $candidate->name }}</td>
                                <td class="px-6 py-4 font-mono text-gray-600">{{ $candidate->cnic ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $candidate->campus?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $candidate->trade?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        Screened
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('registration.show', $candidate->id) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700" title="Manage Registration">
                                            <i class="fas fa-file-alt mr-1"></i> Manage
                                        </a>
                                        <a href="{{ route('candidates.show', $candidate->id) }}" class="inline-flex items-center px-3 py-1.5 bg-cyan-600 text-white text-xs font-medium rounded hover:bg-cyan-700" title="View Profile">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $candidates->links() }}
            </div>
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
            <i class="fas fa-info-circle text-blue-500 text-xl mb-2"></i>
            <p class="text-blue-700">No candidates pending registration.</p>
            <p class="text-blue-600 text-sm mt-1">Candidates will appear here once they complete the screening process (Module 2).</p>
        </div>
    @endif

    {{-- Registered Candidates Table --}}
    @if($registeredCandidates->count())
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">Registered Candidates</h5>
                <p class="text-xs text-gray-500 mt-1">Candidates who have completed registration and allocation</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">TheLeap ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">CNIC</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Program</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Batch</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Registered</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($registeredCandidates as $candidate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-semibold text-gray-900">{{ $candidate->btevta_id }}</td>
                                <td class="px-6 py-4 text-gray-900">{{ $candidate->name }}</td>
                                <td class="px-6 py-4 font-mono text-gray-600">{{ $candidate->cnic ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $candidate->campus?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $candidate->trade?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $candidate->program?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    @if($candidate->batch)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 font-mono">
                                            {{ $candidate->batch->batch_code }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-xs">
                                    {{ $candidate->registration_date ? $candidate->registration_date->format('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('registration.show', $candidate->id) }}" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700" title="View Registration">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                        <a href="{{ route('candidates.show', $candidate->id) }}" class="inline-flex items-center px-3 py-1.5 bg-cyan-600 text-white text-xs font-medium rounded hover:bg-cyan-700" title="View Profile">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $registeredCandidates->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
