@extends('layouts.app')
@section('title', 'Complaint #' . $complaint->id)
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-3xl font-bold">Complaint #{{ $complaint->id }}</h1>
            <p class="text-gray-600 mt-1">Filed by {{ $complaint->complainant->name }}</p>
        </div>
        <div class="flex gap-2">
            <span class="badge badge-{{ $complaint->priority_color }} text-lg">
                {{ ucfirst($complaint->priority) }} Priority
            </span>
            <span class="badge badge-{{ $complaint->status_color }} text-lg">
                {{ ucfirst($complaint->status) }}
            </span>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Complaint Details -->
            <div class="card">
                <h2 class="text-xl font-bold mb-4">{{ $complaint->subject ?? $complaint->title }}</h2>
                <div class="prose max-w-none">
                    <p>{{ $complaint->description }}</p>
                </div>

                <div class="grid md:grid-cols-3 gap-4 mt-6 pt-6 border-t">
                    <div>
                        <p class="text-sm text-gray-600">Category</p>
                        <p class="font-semibold">{{ ucfirst($complaint->complaint_category) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Filed On</p>
                        <p class="font-semibold">{{ $complaint->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Assigned To</p>
                        <p class="font-semibold">{{ $complaint->assignee->name ?? 'Unassigned' }}</p>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <h2 class="text-xl font-bold mb-4">Complaint Timeline</h2>
                <div class="space-y-4">
                    @foreach($complaint->updates as $update)
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-comment text-blue-600"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-1">
                                <p class="font-semibold">{{ $update->user->name }}</p>
                                <span class="text-sm text-gray-500">{{ $update->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-700">{{ $update->message }}</p>
                            @if($update->status_change)
                                <span class="text-xs text-gray-500 mt-1 inline-block">
                                    Status changed to: {{ ucfirst($update->new_status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Update Form -->
            <div class="card">
                <h3 class="text-lg font-bold mb-4">Add Update</h3>
                <form method="POST" action="{{ route('complaints.update-status', $complaint) }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="open" {{ $complaint->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="investigating" {{ $complaint->status == 'investigating' ? 'selected' : '' }}>Investigating</option>
                            <option value="resolved" {{ $complaint->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $complaint->status == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Update Message</label>
                        <textarea name="message" rows="3" class="form-input" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>Post Update
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- SLA Status -->
            <div class="card {{ $complaint->sla_breached ? 'bg-red-50 border-red-300' : 'bg-green-50 border-green-300' }}">
                <h3 class="font-bold mb-3">SLA Status</h3>
                <div class="text-center">
                    <i class="fas fa-clock text-4xl {{ $complaint->sla_breached ? 'text-red-600' : 'text-green-600' }} mb-2"></i>
                    <p class="text-2xl font-bold {{ $complaint->sla_breached ? 'text-red-900' : 'text-green-900' }}">
                        {{ $complaint->hours_remaining }} hours {{ $complaint->sla_breached ? 'overdue' : 'remaining' }}
                    </p>
                    @if($complaint->sla_breached)
                        <p class="text-red-700 text-sm mt-2">SLA Breached!</p>
                    @endif
                </div>
            </div>

            <!-- Details -->
            <div class="card">
                <h3 class="font-bold mb-4">Complaint Details</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">Complainant</p>
                        <p class="font-semibold">{{ $complaint->complainant->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Contact</p>
                        <p class="font-semibold">{{ $complaint->complainant->phone }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Campus</p>
                        <p class="font-semibold">{{ $complaint->campus->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Resolution Target</p>
                        <p class="font-semibold">{{ $complaint->resolution_target_date->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <h3 class="font-bold mb-3">Actions</h3>
                <div class="space-y-2">
                    <button onclick="assignTo()" class="w-full btn btn-secondary btn-sm">
                        <i class="fas fa-user-plus mr-2"></i>Reassign
                    </button>
                    <button onclick="escalate()" class="w-full btn btn-warning btn-sm">
                        <i class="fas fa-level-up-alt mr-2"></i>Escalate
                    </button>
                    @if($complaint->status != 'closed')
                    <button onclick="closeComplaint()" class="w-full btn btn-success btn-sm">
                        <i class="fas fa-check mr-2"></i>Close Complaint
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection