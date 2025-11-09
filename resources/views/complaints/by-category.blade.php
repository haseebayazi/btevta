@extends('layouts.app')
@section('title', 'Complaints by Category')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Complaints - {{ ucfirst(request('category')) }}</h1>
            <p class="text-gray-600 mt-2">Viewing all {{ request('category') }} related complaints</p>
        </div>
        <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Complaints
        </a>
    </div>

    <!-- Category Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Total</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $complaints->count() }}</p>
        </div>
        <div class="card bg-yellow-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Pending</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ $complaints->where('status', 'registered')->count() }}</p>
        </div>
        <div class="card bg-purple-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Investigating</h3>
            <p class="text-3xl font-bold text-purple-600">{{ $complaints->where('status', 'investigating')->count() }}</p>
        </div>
        <div class="card bg-green-50">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">Resolved</h3>
            <p class="text-3xl font-bold text-green-600">{{ $complaints->whereIn('status', ['resolved', 'closed'])->count() }}</p>
        </div>
    </div>

    <!-- Complaints List -->
    <div class="space-y-4">
        @forelse($complaints as $complaint)
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex gap-3 mb-3">
                        <span class="badge badge-{{ $complaint->priority == 'critical' ? 'danger' : ($complaint->priority == 'high' ? 'warning' : 'info') }}">
                            {{ ucfirst($complaint->priority) }} Priority
                        </span>
                        <span class="badge badge-{{ $complaint->status == 'closed' || $complaint->status == 'resolved' ? 'success' : 'warning' }}">
                            {{ ucfirst($complaint->status) }}
                        </span>
                        @if($complaint->assigned_to)
                        <span class="badge badge-secondary">
                            <i class="fas fa-user mr-1"></i>Assigned
                        </span>
                        @endif
                    </div>

                    <h3 class="text-xl font-bold mb-2">
                        <a href="{{ route('complaints.show', $complaint) }}" class="text-blue-600 hover:underline">
                            #{{ $complaint->complaint_number }} - {{ $complaint->subject }}
                        </a>
                    </h3>

                    <p class="text-gray-700 mb-3">{{ Str::limit($complaint->description, 250) }}</p>

                    <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                        @if($complaint->complainant_name)
                        <span>
                            <i class="fas fa-user mr-1"></i>{{ $complaint->complainant_name }}
                        </span>
                        @endif
                        @if($complaint->candidate)
                        <span>
                            <i class="fas fa-id-card mr-1"></i>{{ $complaint->candidate->name }}
                        </span>
                        @endif
                        <span>
                            <i class="fas fa-calendar mr-1"></i>{{ $complaint->created_at->format('M d, Y') }}
                        </span>
                        @if($complaint->assignedTo)
                        <span>
                            <i class="fas fa-user-tie mr-1"></i>{{ $complaint->assignedTo->name }}
                        </span>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2 ml-4">
                    <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    @if(auth()->user()->role == 'admin')
                    <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-edit"></i>
                    </a>
                    @endif
                </div>
            </div>

            @if($complaint->resolution_details)
            <div class="mt-4 p-3 bg-green-50 border-l-4 border-green-500">
                <h4 class="font-semibold text-green-800 mb-1">
                    <i class="fas fa-check-circle mr-1"></i>Resolution
                </h4>
                <p class="text-sm text-green-700">{{ Str::limit($complaint->resolution_details, 150) }}</p>
            </div>
            @endif
        </div>
        @empty
        <div class="card text-center py-16">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500 mb-2">No complaints found in this category</p>
            <p class="text-gray-400">All {{ request('category') }} complaints will appear here</p>
        </div>
        @endforelse
    </div>

    <!-- Export Options -->
    @if($complaints->count() > 0)
    <div class="card mt-6">
        <div class="flex justify-between items-center">
            <h3 class="font-semibold">Export Options</h3>
            <div class="flex gap-2">
                <form action="{{ route('complaints.export') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    <input type="hidden" name="format" value="excel">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                </form>
                <form action="{{ route('complaints.export') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    <input type="hidden" name="format" value="pdf">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-file-pdf mr-1"></i>PDF
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
