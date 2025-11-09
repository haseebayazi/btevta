@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Training Management</h2>
        <a href="{{ route('training.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>New Training
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Active Batches</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['active_batches'] ?? 0 }}</p>
                </div>
                <i class="fas fa-layer-group text-blue-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">In Progress</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['in_progress'] ?? 0 }}</p>
                </div>
                <i class="fas fa-graduation-cap text-yellow-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Completed</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['completed'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-green-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Certificates</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ $stats['completed_count'] ?? 0 }}</p>
                </div>
                <i class="fas fa-certificate text-purple-400 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Active Batches Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Active Training Batches</h3>
        
        <div class="overflow-x-auto">
            @if($activeBatches->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Batch Number</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Candidates</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeBatches as $batch)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold">{{ $batch->batch_number }}</td>
                                <td class="px-6 py-4">{{ $batch->campus->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">{{ $batch->trade->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="font-bold">{{ $batch->candidates_count }}</span> candidates
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                        {{ ucfirst($batch->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('training.show', $batch->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View Details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $activeBatches->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No active training batches</p>
            @endif
        </div>
    </div>
</div>
@endsection