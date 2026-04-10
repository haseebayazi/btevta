@extends('layouts.app')
@section('title', 'Trade Details')
@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $trade->name }}</h2>
            <p class="text-gray-600 mt-1">
                @if($trade->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                @endif
            </p>
        </div>
        <div class="flex items-center space-x-2">
            @can('update', $trade)
            <a href="{{ route('admin.trades.edit', $trade->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            @endcan
            @can('toggleStatus', $trade)
            <form action="{{ route('admin.trades.toggle-status', $trade->id) }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-white {{ $trade->is_active ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700' }}">
                    <i class="fas {{ $trade->is_active ? 'fa-ban' : 'fa-check' }} mr-2"></i>
                    {{ $trade->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            @endcan
            <a href="{{ route('admin.trades.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-blue-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-tools mr-2"></i>Trade Information
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium mb-1">Trade Name</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $trade->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium mb-1">Trade Code</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                            {{ $trade->code }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium mb-1">Category</p>
                        <p class="text-sm text-gray-900">{{ $trade->category ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium mb-1">Duration</p>
                        <p class="text-sm text-gray-900">
                            @if($trade->duration_months)
                                {{ $trade->duration_months }} months
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    @if($trade->description)
                    <div class="md:col-span-2">
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-medium mb-1">Description</p>
                        <p class="text-sm text-gray-900">{{ $trade->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if(isset($trade->candidates) && $trade->candidates->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
                <div class="bg-indigo-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-users mr-2"></i>Associated Candidates
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500 text-white">
                            {{ $trade->candidates->count() }}
                        </span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CNIC</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($trade->candidates as $candidate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $candidate->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $candidate->cnic ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $candidate->status ?? 'Active' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-green-600 px-6 py-4">
                    <h3 class="text-base font-semibold text-white">Statistics</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Candidates</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            {{ $trade->candidates->count() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Active Batches</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                            {{ $trade->batches->count() }}
                        </span>
                    </div>
                    <hr class="border-gray-200">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Created</p>
                        <p class="text-sm text-gray-900">{{ $trade->created_at->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Last Updated</p>
                        <p class="text-sm text-gray-900">{{ $trade->updated_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            @can('delete', $trade)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="bg-red-600 px-6 py-4">
                    <h3 class="text-base font-semibold text-white">Danger Zone</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Permanently delete this trade. Only possible if no candidates or batches are linked.</p>
                    <form action="{{ route('admin.trades.destroy', $trade->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                                onclick="return confirm('Are you sure you want to delete this trade? This cannot be undone.')">
                            <i class="fas fa-trash mr-2"></i>Delete Trade
                        </button>
                    </form>
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection
