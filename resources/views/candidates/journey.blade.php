@extends('layouts.app')

@section('title', 'Candidate Journey - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Candidate Journey</h1>
                <p class="text-gray-600 mt-2">{{ $candidate->name }} - {{ $candidate->btevta_id }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('candidates.show', $candidate) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Candidate
                </a>
            </div>
        </div>
    </div>

    <!-- Progress Overview Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Journey Progress</h3>
                <p class="text-gray-600">Current Stage: <span class="font-semibold text-blue-600">{{ $currentStage }}</span></p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-blue-600">{{ $completionPercentage }}%</div>
                <div class="text-sm text-gray-600">Complete</div>
            </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="bg-blue-600 h-4 rounded-full transition-all duration-500" style="width: {{ $completionPercentage }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Milestones Timeline -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Journey Milestones</h3>

                <div class="relative">
                    <!-- Vertical Timeline Line -->
                    <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                    @foreach($milestones as $index => $milestone)
                    <div class="relative flex items-start mb-8 last:mb-0">
                        <!-- Icon Circle -->
                        <div class="relative z-10 flex items-center justify-center w-16 h-16 rounded-full border-4 border-white shadow-lg
                            {{ $milestone['completed'] ? 'bg-green-500' : 'bg-gray-300' }}">
                            <i class="fas {{ $milestone['icon'] ?? 'fa-circle' }} text-white text-xl"></i>
                        </div>

                        <!-- Content -->
                        <div class="ml-6 flex-1">
                            <div class="bg-gray-50 rounded-lg p-4 {{ $milestone['completed'] ? 'border-l-4 border-green-500' : 'border-l-4 border-gray-300' }}">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $milestone['name'] }}</h4>
                                        @if($milestone['date'])
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="far fa-calendar mr-1"></i>
                                            {{ \Carbon\Carbon::parse($milestone['date'])->format('M d, Y') }}
                                        </p>
                                        @else
                                        <p class="text-sm text-gray-500 mt-1 italic">Not yet completed</p>
                                        @endif
                                    </div>
                                    <div>
                                        @if($milestone['completed'])
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Completed
                                        </span>
                                        @else
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            <i class="far fa-clock mr-1"></i>Pending
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Candidate Info Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Candidate Info</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">BTEVTA ID:</span>
                        <p class="font-semibold">{{ $candidate->btevta_id }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Campus:</span>
                        <p class="font-semibold">{{ $candidate->campus?->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Trade:</span>
                        <p class="font-semibold">{{ $candidate->trade?->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Current Status:</span>
                        <p>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Milestones Completed:</span>
                        <span class="font-semibold">{{ collect($milestones)->where('completed', true)->count() }}/{{ count($milestones) }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Days Since Listing:</span>
                        <span class="font-semibold">{{ $candidate->created_at->diffInDays(now()) }} days</span>
                    </div>
                    @if($candidate->registered_at)
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Days Since Registration:</span>
                        <span class="font-semibold">{{ $candidate->registered_at->diffInDays(now()) }} days</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Recent Activities Card -->
            @if($activities->isNotEmpty())
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activities</h3>
                <div class="space-y-3">
                    @foreach($activities->take(5) as $activity)
                    <div class="flex items-start space-x-3 pb-3 border-b last:border-b-0 last:pb-0">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-{{ $activity['color'] ?? 'gray' }}-100 flex items-center justify-center">
                                <i class="fas {{ $activity['icon'] ?? 'fa-circle' }} text-{{ $activity['color'] ?? 'gray' }}-600 text-sm"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($activity['date'])->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Custom animations for timeline */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .relative.flex {
        animation: fadeInUp 0.5s ease-out;
    }
</style>
@endpush
@endsection
