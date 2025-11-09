@extends('layouts.app')
@section('title', 'Active Departure Issues')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Active Departure Issues</h1>
        <button onclick="createIssue()" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Report Issue
        </button>
    </div>

    <!-- Issue Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">High Priority</p>
            <p class="text-3xl font-bold text-red-900">{{ $highPriorityCount }}</p>
        </div>
        <div class="card bg-orange-50">
            <p class="text-sm text-orange-800">Medium Priority</p>
            <p class="text-3xl font-bold text-orange-900">{{ $mediumPriorityCount }}</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">Low Priority</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $lowPriorityCount }}</p>
        </div>
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">In Progress</p>
            <p class="text-3xl font-bold text-blue-900">{{ $inProgressCount }}</p>
        </div>
    </div>

    <!-- Active Issues List -->
    <div class="space-y-4">
        @forelse($activeIssues as $issue)
        <div class="card border-l-4 {{ $issue->priority_border_color }}">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="badge badge-{{ $issue->priority_color }}">
                            {{ ucfirst($issue->priority) }} Priority
                        </span>
                        <span class="badge badge-{{ $issue->status_color }}">
                            {{ ucfirst($issue->status) }}
                        </span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">{{ $issue->title }}</h3>
                    <p class="text-gray-700 mb-3">{{ $issue->description }}</p>
                    
                    <div class="grid md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Candidate</p>
                            <p class="font-semibold">{{ $issue->departure->candidate->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Reported By</p>
                            <p class="font-semibold">{{ $issue->reported_by->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Reported On</p>
                            <p class="font-semibold">{{ $issue->created_at->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Assigned To</p>
                            <p class="font-semibold">{{ $issue->assigned_to->name ?? 'Unassigned' }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 ml-4">
                    <button onclick="viewIssue({{ $issue->id }})" class="btn btn-sm btn-secondary">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="resolveIssue({{ $issue->id }})" class="btn btn-sm btn-success">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>

            @if($issue->updates->count() > 0)
            <div class="border-t pt-3 mt-3">
                <p class="text-sm font-medium text-gray-700 mb-2">Latest Update:</p>
                <p class="text-sm text-gray-600">{{ $issue->updates->first()->message }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    by {{ $issue->updates->first()->user->name }} - 
                    {{ $issue->updates->first()->created_at->diffForHumans() }}
                </p>
            </div>
            @endif
        </div>
        @empty
        <div class="card text-center py-12">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-xl font-semibold text-gray-700">No active issues!</p>
        </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $activeIssues->links() }}
    </div>
</div>

@push('scripts')
<script>
function createIssue() {
    window.location.href = "{{ route('departure.issues.create') }}";
}

function viewIssue(id) {
    window.location.href = `/departure/issues/${id}`;
}

function resolveIssue(id) {
    if (confirm('Mark this issue as resolved?')) {
        // Submit resolution
    }
}
</script>
@endpush
@endsection