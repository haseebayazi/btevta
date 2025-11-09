@extends('layouts.app')
@section('title', 'Departure Timeline')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Departure Process Timeline</h1>
    <p class="text-gray-600 mb-8">{{ $departure->candidate->name }} - {{ $departure->destination_country }}</p>
    
    <div class="space-y-6">
        @foreach($stages as $index => $stage)
        <div class="card {{ $stage['completed'] ? 'bg-green-50' : '' }}">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center {{ $stage['completed'] ? 'bg-green-500' : 'bg-gray-300' }}">
                        <i class="fas fa-{{ $stage['icon'] }} text-white text-xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold">{{ $stage['title'] }}</h3>
                        @if($stage['completed'])
                            <span class="badge badge-success">Completed</span>
                        @elseif($stage['in_progress'])
                            <span class="badge badge-info">In Progress</span>
                        @else
                            <span class="badge badge-secondary">Pending</span>
                        @endif
                    </div>
                    <p class="text-gray-600 mb-3">{{ $stage['description'] }}</p>
                    
                    @if($stage['completed'])
                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Completed On</p>
                            <p class="font-semibold">{{ $stage['completed_date']->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Days Taken</p>
                            <p class="font-semibold">{{ $stage['days_taken'] }} days</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Completed By</p>
                            <p class="font-semibold">{{ $stage['completed_by'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- 90-Day Countdown -->
    <div class="card mt-8 {{ $departure->is_within_90_days ? 'bg-red-50 border-red-300' : 'bg-blue-50' }}">
        <div class="flex items-center gap-4">
            <i class="fas fa-clock text-4xl {{ $departure->is_within_90_days ? 'text-red-600' : 'text-blue-600' }}"></i>
            <div>
                <h3 class="text-xl font-bold">90-Day Compliance Status</h3>
                <p class="text-lg mt-2">
                    <span class="font-bold text-2xl">{{ $departure->days_remaining }}</span> days remaining
                </p>
                @if($departure->is_within_90_days)
                    <p class="text-red-700 mt-2 font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Within 90-day window - Urgent attention required!
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection