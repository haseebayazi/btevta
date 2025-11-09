@extends('layouts.app')
@section('title', 'My Assigned Complaints')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">My Assigned Complaints</h1>

    <!-- Quick Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Assigned</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalAssigned }}</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Due Today</p>
            <p class="text-3xl font-bold text-red-900">{{ $dueToday }}</p>
        </div>
        <div class="card bg-orange-50">
            <p class="text-sm text-orange-800">Overdue</p>
            <p class="text-3xl font-bold text-orange-900">{{ $overdue }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Resolved This Week</p>
            <p class="text-3xl font-bold text-green-900">{{ $resolvedThisWeek }}</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button class="tab-link active" data-tab="all">All</button>
                <button class="tab-link" data-tab="due-today">Due Today</button>
                <button class="tab-link" data-tab="overdue">Overdue</button>
                <button class="tab-link" data-tab="investigating">Investigating</button>
            </nav>
        </div>
    </div>

    <!-- Complaints List -->
    <div class="space-y-4">
        @forelse($myComplaints as $complaint)
        <div class="card border-l-4 {{ $complaint->priority_border_color }} hover:shadow-lg transition">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="badge badge-{{ $complaint->priority_color }}">
                            {{ ucfirst($complaint->priority) }}
                        </span>
                        <span class="badge badge-{{ $complaint->status_color }}">
                            {{ ucfirst($complaint->status) }}
                        </span>
                        @if($complaint->is_sla_breached)
                            <span class="badge badge-danger">
                                <i class="fas fa-exclamation-circle mr-1"></i>SLA Breached
                            </span>
                        @endif
                    </div>
                    
                    <h3 class="text-xl font-bold mb-2">
                        <a href="{{ route('complaints.show', $complaint) }}" class="text-blue-600 hover:text-blue-700">
                            #{{ $complaint->id }} - {{ $complaint->title }}
                        </a>
                    </h3>
                    
                    <p class="text-gray-700 mb-3">{{ Str::limit($complaint->description, 150) }}</p>
                    
                    <div class="flex items-center gap-6 text-sm text-gray-600">
                        <span>
                            <i class="fas fa-user mr-1"></i>
                            {{ $complaint->complainant->name }}
                        </span>
                        <span>
                            <i class="fas fa-calendar mr-1"></i>
                            {{ $complaint->created_at->diffForHumans() }}
                        </span>
                        <span>
                            <i class="fas fa-clock mr-1"></i>
                            {{ $complaint->hours_until_due > 0 ? $complaint->hours_until_due . 'h remaining' : 'Overdue' }}
                        </span>
                    </div>
                </div>
                
                <div class="flex gap-2 ml-4">
                    <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-primary">
                        View & Update
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="card text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
            <p class="text-xl font-semibold text-gray-600">No complaints assigned to you</p>
        </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $myComplaints->links() }}
    </div>
</div>
@endsection