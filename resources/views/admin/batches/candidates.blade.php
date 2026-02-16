@extends('layouts.app')
@section('title', 'Batch Candidates - ' . $batch->batch_code)
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
                <a href="{{ route('admin.batches.show', $batch->id) }}" class="hover:text-blue-600">{{ $batch->batch_code }}</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Candidates</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Batch Candidates</h2>
            <p class="text-gray-500 text-sm mt-1">
                {{ $batch->batch_code }} {{ $batch->name ? '- ' . $batch->name : '' }} | {{ $batch->trade->name ?? 'N/A' }} | {{ $batch->campus->name ?? 'N/A' }}
            </p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('admin.batches.show', $batch->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Batch
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    @php
        $enrollmentCount = $candidates->total();
        $availableSlots = max(0, $batch->capacity - $enrollmentCount);
        $progressPct = $batch->capacity > 0 ? round(($enrollmentCount / $batch->capacity) * 100) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Enrolled</p>
                    <h3 class="text-2xl font-bold text-blue-600">{{ $enrollmentCount }}</h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-check text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Capacity</p>
                    <h3 class="text-2xl font-bold text-green-600">{{ $batch->capacity }}</h3>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-th-large text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Available Slots</p>
                    <h3 class="text-2xl font-bold {{ $availableSlots > 0 ? 'text-cyan-600' : 'text-red-600' }}">{{ $availableSlots }}</h3>
                </div>
                <div class="w-12 h-12 bg-cyan-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chair text-cyan-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Enrollment</p>
                    <h3 class="text-2xl font-bold text-yellow-600">{{ $progressPct }}%</h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-pie text-yellow-600"></i>
                </div>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $progressPct >= 90 ? 'bg-red-500' : ($progressPct >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min(100, $progressPct) }}%"></div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center justify-between">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    {{-- Search and Filter --}}
    <div class="bg-white rounded-xl shadow-sm border p-5">
        <form method="GET" action="{{ route('admin.batches.candidates', $batch->id) }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" placeholder="Name, ID, CNIC..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       value="{{ request('search') }}">
            </div>
            <div class="min-w-[170px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Training Status</label>
                <select name="training_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="enrolled" {{ request('training_status') == 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                    <option value="in_progress" {{ request('training_status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('training_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="dropped" {{ request('training_status') == 'dropped' ? 'selected' : '' }}>Dropped</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'training_status']))
                <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Candidates Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-3 border-b">
            <h5 class="font-semibold text-gray-800"><i class="fas fa-users mr-2"></i>Candidates ({{ $candidates->total() }})</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">TheLeap ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">CNIC</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Trade</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Training Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Assigned Date</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($candidates as $candidate)
                    @php
                        $trainingStatusClasses = [
                            'enrolled' => 'bg-blue-100 text-blue-800',
                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'dropped' => 'bg-red-100 text-red-800',
                        ];
                        $trainingStatusClass = $trainingStatusClasses[$candidate->training_status] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-medium text-blue-700">{{ $candidate->btevta_id }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $candidate->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $candidate->formatted_cnic ?? $candidate->cnic }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $candidate->trade->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trainingStatusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $candidate->training_status ?? 'N/A')) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $candidate->training_start_date ? \Carbon\Carbon::parse($candidate->training_start_date)->format('d M Y') : 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @can('view', $candidate)
                            <a href="{{ route('candidates.show', $candidate->id) }}" class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 p-1.5 rounded" title="View Candidate">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-users-slash text-4xl text-gray-300 mb-3 block"></i>
                            <p>No candidates found in this batch</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($candidates->hasPages())
    <div class="mt-4">
        {{ $candidates->links() }}
    </div>
    @endif
</div>
@endsection