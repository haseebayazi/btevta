@extends('layouts.app')
@section('title', 'Departure Details')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Departure Process</h1>
            <p class="text-gray-600">{{ $departure->candidate->name }} - {{ $departure->destination_country }}</p>
        </div>
        <a href="{{ route('departure.timeline', $departure) }}" class="btn btn-primary">
            <i class="fas fa-timeline mr-2"></i>View Timeline
        </a>
    </div>

    <!-- 90-Day Countdown Alert -->
    @if($departure->is_within_90_days)
    <div class="card bg-red-50 border-red-300 mb-6">
        <div class="flex items-center gap-4">
            <i class="fas fa-exclamation-triangle text-4xl text-red-600"></i>
            <div>
                <h3 class="text-lg font-bold text-red-900">90-Day Compliance Alert</h3>
                <p class="text-red-700">Only <strong>{{ $departure->days_remaining }}</strong> days remaining!</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Stage Checklist -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Departure Checklist ({{ $completedStages }}/6 Complete)</h2>
        <div class="space-y-3">
            @foreach($departure->stages as $index => $stage)
            <div class="flex items-center gap-4 p-4 border rounded {{ $stage->completed ? 'bg-green-50' : 'bg-gray-50' }}">
                <i class="fas fa-{{ $stage->completed ? 'check-circle text-green-600' : 'circle text-gray-400' }} text-2xl"></i>
                <div class="flex-1">
                    <h4 class="font-semibold">{{ $stage->title }}</h4>
                    <p class="text-sm text-gray-600">{{ $stage->description }}</p>
                </div>
                @if($stage->completed)
                    <span class="text-sm text-green-700">
                        {{ $stage->completed_at->format('M d, Y') }}
                    </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Issues -->
    @if($departure->active_issues_count > 0)
    <div class="card border-red-300">
        <h3 class="text-lg font-bold mb-3 text-red-900">Active Issues ({{ $departure->active_issues_count }})</h3>
        <a href="{{ route('departure.active-issues') }}" class="btn btn-danger">
            View All Issues
        </a>
    </div>
    @endif
</div>
@endsection