@extends('layouts.app')
@section('title', 'Registration Management')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Registration Management</h2>
            <p class="text-gray-500 text-sm mt-1">Manage candidate registrations and documentation</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-blue-600 text-white rounded-lg shadow-sm p-6">
            <h5 class="text-sm font-medium opacity-90">Pending Registrations</h5>
            <p class="text-3xl font-bold mt-2">{{ $candidates->total() }}</p>
        </div>
    </div>

    @if($candidates->count())
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">Candidates Pending Registration</h5>
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
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Documents</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Next of Kin</th>
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
                                    @php
                                        $docsCount = $candidate->documents->count();
                                    @endphp
                                    @if($docsCount > 0)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            {{ $docsCount }} uploaded
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            None
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($candidate->nextOfKin)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>Added
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">
                                            <i class="fas fa-times mr-1"></i>Missing
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $candidate->status === 'registered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
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
            <p class="text-blue-700">No candidates pending registration found.</p>
        </div>
    @endif
</div>
@endsection
