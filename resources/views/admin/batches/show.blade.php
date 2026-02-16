@extends('layouts.app')
@section('title', 'Batch Details - ' . $batch->batch_code)
@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('admin.batches.index') }}" class="hover:text-blue-600">Batches</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">{{ $batch->batch_code }}</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">{{ $batch->batch_code }}</h2>
            @if($batch->name)
            <p class="text-gray-500 text-sm mt-1">{{ $batch->name }}</p>
            @endif
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            @can('update', $batch)
            <a href="{{ route('admin.batches.edit', $batch->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-users mr-1"></i> Candidates
            </a>
            <a href="{{ route('admin.batches.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    @php
        $candidateCount = $batch->candidates ? $batch->candidates->count() : 0;
        $availableSeats = $batch->capacity - $candidateCount;
        $fillPercentage = $batch->capacity > 0 ? round(($candidateCount / $batch->capacity) * 100) : 0;
        $statusClasses = [
            'planned' => 'bg-blue-100 text-blue-800',
            'active' => 'bg-green-100 text-green-800',
            'completed' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-1 {{ $statusClasses[$batch->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($batch->status) }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-flag text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Enrolled</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $candidateCount }}</h3>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Capacity</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $batch->capacity }}</h3>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-th-large text-purple-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Available</p>
                    <h3 class="text-2xl font-bold {{ $availableSeats > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $availableSeats }}</h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chair text-yellow-600"></i>
                </div>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $fillPercentage >= 90 ? 'bg-red-500' : ($fillPercentage >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min(100, $fillPercentage) }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $fillPercentage }}% filled</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Batch Information --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="bg-blue-600 text-white px-5 py-3">
                <h5 class="font-semibold"><i class="fas fa-info-circle mr-2"></i>Batch Information</h5>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 block">Batch Code</label>
                        <p class="font-mono font-medium text-blue-700">{{ $batch->batch_code }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block">Trade</label>
                        <p class="text-gray-800">{{ $batch->trade->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block">Campus</label>
                        <p class="text-gray-800">{{ $batch->campus->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block">Trainer</label>
                        <p class="text-gray-800">{{ $batch->trainer_name ?? ($batch->trainer ? $batch->trainer->name : 'N/A') }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block">Start Date</label>
                        <p class="text-gray-800">{{ $batch->start_date ? $batch->start_date->format('d M Y') : 'Not Set' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block">End Date</label>
                        <p class="text-gray-800">{{ $batch->end_date ? $batch->end_date->format('d M Y') : 'Not Set' }}</p>
                    </div>
                </div>
                @if($batch->description)
                <div class="mt-4 pt-4 border-t">
                    <label class="text-xs font-medium text-gray-500 block">Description</label>
                    <p class="text-gray-700 text-sm mt-1">{{ $batch->description }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="bg-cyan-500 text-white px-5 py-3">
                    <h5 class="font-semibold"><i class="fas fa-bolt mr-2"></i>Quick Actions</h5>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-users text-green-600 mr-3 w-5 text-center"></i>
                        <span class="text-sm font-medium text-gray-700">View Candidates</span>
                    </a>
                    @can('update', $batch)
                    <a href="{{ route('admin.batches.edit', $batch->id) }}" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-edit text-yellow-600 mr-3 w-5 text-center"></i>
                        <span class="text-sm font-medium text-gray-700">Edit Batch</span>
                    </a>
                    @endcan
                    @if($batch->status === 'active')
                    <a href="{{ route('training.dual-status-dashboard', $batch->id) }}" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-chart-bar text-blue-600 mr-3 w-5 text-center"></i>
                        <span class="text-sm font-medium text-gray-700">Training Dashboard</span>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Danger Zone --}}
            @can('delete', $batch)
            <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-red-200">
                    <h5 class="font-semibold text-red-700"><i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone</h5>
                </div>
                <div class="p-4">
                    <form method="POST" action="{{ route('admin.batches.destroy', $batch->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm"
                                onclick="return confirm('Delete this batch? This cannot be undone.')">
                            <i class="fas fa-trash mr-1"></i> Delete Batch
                        </button>
                    </form>
                </div>
            </div>
            @endcan
        </div>
    </div>

    {{-- Enrolled Candidates --}}
    @if($batch->candidates && $batch->candidates->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <h5 class="font-semibold text-gray-800"><i class="fas fa-users mr-2"></i>Enrolled Candidates ({{ $batch->candidates->count() }})</h5>
            <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">TheLeap ID</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($batch->candidates->take(10) as $candidate)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $candidate->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $candidate->btevta_id }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $candidateStatusClass = match($candidate->status) {
                                    'training' => 'bg-blue-100 text-blue-800',
                                    'training_completed', 'visa_process' => 'bg-green-100 text-green-800',
                                    'registered' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $candidateStatusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($batch->candidates->count() > 10)
        <div class="px-5 py-3 bg-gray-50 text-center">
            <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View all {{ $batch->candidates->count() }} candidates <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection