@extends('layouts.app')
@section('title', 'Batches Management')
@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Batches Management</h2>
            <p class="text-gray-500 text-sm mt-1">Manage training batches and candidate assignments</p>
        </div>
        @can('create', App\Models\Batch::class)
        <a href="{{ route('admin.batches.create') }}" class="mt-3 sm:mt-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add New Batch
        </a>
        @endcan
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
        <form method="GET" action="{{ route('admin.batches.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" placeholder="Batch code, name..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       value="{{ request('search') }}">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Campus</label>
                <select name="campus_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $id => $name)
                        <option value="{{ $id }}" {{ request('campus_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Trade</label>
                <select name="trade_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Trades</option>
                    @foreach($trades as $id => $name)
                        <option value="{{ $id }}" {{ request('trade_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'campus_id', 'trade_id', 'district']))
                <a href="{{ route('admin.batches.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Batches Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Batch Code</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Trade</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Campus</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Start Date</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Candidates</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $batch)
                    @php
                        $statusColors = [
                            \App\Models\Batch::STATUS_PLANNED => 'bg-blue-100 text-blue-800',
                            \App\Models\Batch::STATUS_ACTIVE => 'bg-green-100 text-green-800',
                            \App\Models\Batch::STATUS_COMPLETED => 'bg-gray-100 text-gray-800',
                            \App\Models\Batch::STATUS_CANCELLED => 'bg-red-100 text-red-800',
                        ];
                        $batchStatuses = \App\Models\Batch::getStatuses();
                        $statusClass = $statusColors[$batch->status] ?? 'bg-gray-100 text-gray-800';
                        $statusLabel = $batchStatuses[$batch->status] ?? ucfirst($batch->status);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono font-medium text-blue-700">{{ $batch->batch_code }}</span>
                            @if($batch->name)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $batch->name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $batch->trade->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $batch->campus->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $batch->start_date ? $batch->start_date->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $batch->candidates_count ?? 0 }} / {{ $batch->capacity }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                @can('view', $batch)
                                <a href="{{ route('admin.batches.show', $batch->id) }}" class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 p-1.5 rounded" title="View Details">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('admin.batches.candidates', $batch->id) }}" class="bg-green-50 text-green-600 hover:bg-green-100 p-1.5 rounded" title="View Candidates">
                                    <i class="fas fa-users text-xs"></i>
                                </a>
                                @endcan
                                @can('update', $batch)
                                <a href="{{ route('admin.batches.edit', $batch->id) }}" class="bg-yellow-50 text-yellow-600 hover:bg-yellow-100 p-1.5 rounded" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                @endcan
                                @can('delete', $batch)
                                <form method="POST" action="{{ route('admin.batches.destroy', $batch->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 p-1.5 rounded" title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this batch?')">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl text-gray-300 mb-3 block"></i>
                            <p>No batches found</p>
                            @can('create', App\Models\Batch::class)
                            <a href="{{ route('admin.batches.create') }}" class="text-blue-600 hover:text-blue-800 text-sm mt-1 inline-block">Create a batch</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($batches->hasPages())
    <div class="mt-4">
        {{ $batches->links() }}
    </div>
    @endif
</div>
@endsection