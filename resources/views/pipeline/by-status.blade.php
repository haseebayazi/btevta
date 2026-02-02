@extends('layouts.app')

@section('title', $statusLabel . ' Candidates')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('pipeline.index') }}" class="hover:text-blue-600">Pipeline</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ $statusLabel }}</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">{{ $statusLabel }} Candidates</h1>
                <p class="text-gray-600 mt-1">{{ $candidates->total() }} candidates with status: {{ $statusLabel }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('pipeline.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Pipeline
                </a>
            </div>
        </div>
    </div>

    <!-- Candidates Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            @if($candidates->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TheLeap ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CNIC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OEP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($candidates as $candidate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $candidate->btevta_id ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $candidate->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $candidate->email ?? 'No email' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $candidate->cnic }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $candidate->campus?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $candidate->trade?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $candidate->batch?->name ?? 'Not Assigned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $candidate->oep?->name ?? 'Not Assigned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $candidate->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('candidates.show', $candidate) }}" 
                                           class="text-blue-600 hover:text-blue-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('candidates.journey', $candidate) }}" 
                                           class="text-purple-600 hover:text-purple-900" title="View Journey">
                                            <i class="fas fa-route"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $candidates->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-users-slash text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No candidates found with status: {{ $statusLabel }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
