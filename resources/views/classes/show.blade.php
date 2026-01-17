@extends('layouts.app')

@section('title', 'View Training Class')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">{{ $class->class_name }}</h1>
            <p class="text-gray-600 mt-1">Class Code: <span class="font-mono">{{ $class->class_code }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('classes.edit', $class) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Class Details -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>Class Details
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Campus</label>
                        <p class="font-medium">{{ $class->campus->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Trade</label>
                        <p class="font-medium">{{ $class->trade->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Instructor</label>
                        <p class="font-medium">{{ $class->instructor->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Batch</label>
                        <p class="font-medium">{{ $class->batch->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Room Number</label>
                        <p class="font-medium">{{ $class->room_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Status</label>
                        <p>
                            <span class="badge badge-{{ $class->status_badge_color }}">
                                {{ ucfirst($class->status) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Start Date</label>
                        <p class="font-medium">{{ $class->start_date?->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">End Date</label>
                        <p class="font-medium">{{ $class->end_date?->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                    @if($class->schedule)
                    <div class="md:col-span-2">
                        <label class="text-sm text-gray-500">Schedule</label>
                        <p class="font-medium">{{ $class->schedule }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Enrolled Candidates -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">
                        <i class="fas fa-users mr-2 text-green-500"></i>Enrolled Candidates
                    </h2>
                    @if(!$class->is_full)
                        <span class="text-sm text-gray-500">{{ $class->available_slots }} slots available</span>
                    @endif
                </div>

                @if($class->candidates && $class->candidates->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>TheLeap ID</th>
                                    <th>Status</th>
                                    <th>Enrolled Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($class->candidates as $candidate)
                                <tr>
                                    <td>{{ $candidate->name }}</td>
                                    <td class="font-mono text-sm">{{ $candidate->btevta_id }}</td>
                                    <td>
                                        <span class="badge badge-{{ $candidate->pivot->status == 'enrolled' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($candidate->pivot->status ?? 'enrolled') }}
                                        </span>
                                    </td>
                                    <td>{{ $candidate->pivot->created_at ? \Carbon\Carbon::parse($candidate->pivot->created_at)->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-sm btn-info" title="View Candidate">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('classes.remove-candidate', [$class, $candidate]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Remove this candidate from the class?')"
                                                        title="Remove from Class">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-user-graduate text-4xl mb-3"></i>
                        <p>No candidates enrolled yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Enrollment Stats -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-chart-pie mr-2 text-purple-500"></i>Enrollment
                </h2>
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-32 h-32 transform -rotate-90">
                            <circle cx="64" cy="64" r="56" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                            <circle cx="64" cy="64" r="56" fill="none" stroke="{{ $class->is_full ? '#ef4444' : '#3b82f6' }}"
                                    stroke-width="12"
                                    stroke-dasharray="{{ 352 * ($class->capacity_percentage / 100) }} 352"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center">
                            <span class="text-2xl font-bold">{{ $class->current_enrollment }}</span>
                            <span class="text-gray-500">/{{ $class->max_capacity }}</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">{{ number_format($class->capacity_percentage, 1) }}% Capacity</p>
                    @if($class->is_full)
                        <p class="mt-2 text-red-500 font-medium">
                            <i class="fas fa-exclamation-circle mr-1"></i>Class is Full
                        </p>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-tachometer-alt mr-2 text-blue-500"></i>Quick Stats
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Duration</span>
                        <span class="font-medium">
                            @if($class->start_date && $class->end_date)
                                {{ $class->start_date->diffInDays($class->end_date) }} days
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Enrolled</span>
                        <span class="font-medium">{{ $class->current_enrollment }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Available Slots</span>
                        <span class="font-medium">{{ $class->available_slots }}</span>
                    </div>
                    @if($class->start_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Days Until Start</span>
                        <span class="font-medium">
                            @if($class->start_date->isPast())
                                Started
                            @else
                                {{ now()->diffInDays($class->start_date) }} days
                            @endif
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-cogs mr-2 text-gray-500"></i>Actions
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('classes.edit', $class) }}" class="btn btn-warning w-full justify-center">
                        <i class="fas fa-edit mr-2"></i>Edit Class
                    </a>
                    @if(!$class->is_full)
                        <button type="button" class="btn btn-success w-full justify-center" onclick="document.getElementById('assignModal').classList.remove('hidden')">
                            <i class="fas fa-user-plus mr-2"></i>Assign Candidates
                        </button>
                    @endif
                    <form action="{{ route('classes.destroy', $class) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full justify-center"
                                onclick="return confirm('Are you sure you want to delete this class? This action cannot be undone.')">
                            <i class="fas fa-trash mr-2"></i>Delete Class
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Candidates Modal (placeholder) -->
<div id="assignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Assign Candidates</h3>
            <button onclick="document.getElementById('assignModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p class="text-gray-600 mb-4">Select candidates to assign to this class. Available slots: {{ $class->available_slots }}</p>
        <form action="{{ route('classes.assign-candidates', $class) }}" method="POST">
            @csrf
            <div class="max-h-64 overflow-y-auto border rounded-lg p-3 mb-4">
                <p class="text-sm text-gray-500">Candidate selection will be implemented based on available candidates in training status.</p>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" class="btn btn-secondary">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i>Assign Selected
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
