@extends('layouts.app')
@section('title', 'Complaints')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Complaints Management</h1>
        <div class="flex gap-2">
            <a href="{{ route('complaints.analytics') }}" class="btn btn-secondary">
                <i class="fas fa-chart-line mr-2"></i>Analytics
            </a>
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>New Complaint
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-5 gap-4">
            <div>
                <select name="status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="open">Open</option>
                    <option value="investigating">Investigating</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div>
                <select name="priority" class="form-input">
                    <option value="">All Priorities</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <div>
                <select name="category" class="form-input">
                    <option value="">All Categories</option>
                    <option value="service">Service</option>
                    <option value="facility">Facility</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div>
                <input type="text" name="search" placeholder="Search..." class="form-input">
            </div>
            <div>
                <button type="submit" class="btn btn-primary w-full">Filter</button>
            </div>
        </form>
    </div>

    <!-- Complaints List -->
    <div class="space-y-4">
        @forelse($complaints as $complaint)
        <div class="card border-l-4 {{ $complaint->priority_border_color }}">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex gap-3 mb-2">
                        <span class="badge badge-{{ $complaint->priority_color }}">{{ ucfirst($complaint->priority) }}</span>
                        <span class="badge badge-{{ $complaint->status_color }}">{{ ucfirst($complaint->status) }}</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">
                        <a href="{{ route('complaints.show', $complaint) }}" class="text-blue-600 hover:underline">
                            #{{ $complaint->id }} - {{ $complaint->title }}
                        </a>
                    </h3>
                    <p class="text-gray-700 mb-3">{{ Str::limit($complaint->description, 200) }}</p>
                    <div class="flex gap-4 text-sm text-gray-600">
                        <span><i class="fas fa-user mr-1"></i>{{ $complaint->complainant->name }}</span>
                        <span><i class="fas fa-calendar mr-1"></i>{{ $complaint->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-primary">View</a>
            </div>
        </div>
        @empty
        <div class="card text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No complaints found</p>
        </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $complaints->links() }}
    </div>
</div>
@endsection