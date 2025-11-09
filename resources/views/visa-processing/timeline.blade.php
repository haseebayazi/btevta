@extends('layouts.app')
@section('title', 'Visa Processing Timeline')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Visa Processing Timeline</h1>
        <p class="text-gray-600 mt-1">{{ $visaProcessing->candidate->name }} - {{ $visaProcessing->visa_type }}</p>
    </div>

    <!-- Timeline -->
    <div class="relative">
        <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-300"></div>
        
        <div class="space-y-8">
            @foreach($stages as $stage)
            <div class="relative pl-20">
                <div class="absolute left-0 top-0 w-16 h-16 rounded-full flex items-center justify-center
                            {{ $stage->completed ? 'bg-green-500' : ($stage->current ? 'bg-blue-500' : 'bg-gray-300') }}">
                    <i class="fas {{ $stage->icon }} text-white text-2xl"></i>
                </div>
                
                <div class="card {{ $stage->completed ? 'bg-green-50' : ($stage->current ? 'bg-blue-50' : '') }}">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-xl font-bold">{{ $stage->name }}</h3>
                            <p class="text-gray-600 text-sm mt-1">Stage {{ $stage->number }} of 8</p>
                        </div>
                        <span class="badge badge-{{ $stage->status_color }}">
                            {{ ucfirst($stage->status) }}
                        </span>
                    </div>

                    @if($stage->completed)
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Completed Date</p>
                            <p class="font-semibold">{{ $stage->completed_at->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Duration</p>
                            <p class="font-semibold">{{ $stage->duration_days }} days</p>
                        </div>
                    </div>
                    @elseif($stage->current)
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Started On</p>
                            <p class="font-semibold">{{ $stage->started_at->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Days in Progress</p>
                            <p class="font-semibold">{{ $stage->days_in_progress }} days</p>
                        </div>
                    </div>
                    @endif

                    @if($stage->notes)
                    <div class="mt-3 pt-3 border-t">
                        <p class="text-sm text-gray-700">{{ $stage->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-4 gap-4 mt-8">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Duration</p>
            <p class="text-2xl font-bold text-blue-900">{{ $totalDuration }} days</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Completed Stages</p>
            <p class="text-2xl font-bold text-green-900">{{ $completedStages }}/8</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Current Stage</p>
            <p class="text-2xl font-bold text-purple-900">{{ $currentStage }}</p>
        </div>
        <div class="card bg-orange-50">
            <p class="text-sm text-orange-800">Estimated Completion</p>
            <p class="text-2xl font-bold text-orange-900">{{ $estimatedDays }} days</p>
        </div>
    </div>
</div>
@endsection