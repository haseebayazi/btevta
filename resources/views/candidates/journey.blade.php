@extends('layouts.app')

@section('title', 'Candidate Journey - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('candidates.index') }}" class="hover:text-blue-600">Candidates</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('candidates.show', $candidate) }}" class="hover:text-blue-600">{{ $candidate->name }}</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">Journey</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Candidate Journey</h1>
                <p class="text-gray-600 mt-1">Complete lifecycle tracking for {{ $candidate->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('candidates.journey.export-pdf', $candidate) }}" 
                   class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                </a>
                <a href="{{ route('candidates.show', $candidate) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Candidate
                </a>
            </div>
        </div>
    </div>

    <!-- Progress Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Current Stage -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Current Stage</p>
                    <p class="text-xl font-bold text-{{ $currentStage['color'] }}-600">{{ $currentStage['name'] }}</p>
                </div>
                <div class="w-14 h-14 bg-{{ $currentStage['color'] }}-100 rounded-full flex items-center justify-center">
                    <i class="{{ $currentStage['icon'] }} text-{{ $currentStage['color'] }}-600 text-2xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4">Module {{ $currentStage['module'] ?? 'N/A' }}</p>
        </div>

        <!-- Progress -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Progress</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $progressPercentage }}%</p>
                </div>
                <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>

        <!-- Estimated Completion -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Est. Completion</p>
                    <p class="text-xl font-bold text-green-600">
                        @if($estimatedCompletion)
                            {{ \Carbon\Carbon::parse($estimatedCompletion)->format('M d, Y') }}
                        @else
                            Completed
                        @endif
                    </p>
                </div>
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Blockers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Blockers</p>
                    <p class="text-3xl font-bold {{ count($blockers) > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ count($blockers) }}
                    </p>
                </div>
                <div class="w-14 h-14 {{ count($blockers) > 0 ? 'bg-red-100' : 'bg-green-100' }} rounded-full flex items-center justify-center">
                    <i class="fas {{ count($blockers) > 0 ? 'fa-exclamation-triangle text-red-600' : 'fa-check-circle text-green-600' }} text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Blockers Alert (if any) -->
    @if(count($blockers) > 0)
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Issues Blocking Progress</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($blockers as $blocker)
                        <li>{{ $blocker['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Next Actions -->
    @if(count($nextActions) > 0)
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-tasks text-blue-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Next Required Actions</h3>
                <div class="mt-2 space-y-2">
                    @foreach($nextActions as $action)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-700">{{ $action['action'] }}</span>
                            @if(isset($action['route']))
                                <a href="{{ route($action['route'], $action['params'] ?? []) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Take Action →
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Journey Timeline -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Journey Timeline</h2>
                
                <div class="relative">
                    @foreach($journey as $index => $stage)
                        <div class="flex items-start mb-8 last:mb-0">
                            <!-- Timeline Line -->
                            <div class="flex flex-col items-center mr-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    @if($stage['status'] === 'completed')
                                        bg-green-500 text-white
                                    @elseif($stage['status'] === 'in_progress')
                                        bg-blue-500 text-white animate-pulse
                                    @else
                                        bg-gray-200 text-gray-500
                                    @endif">
                                    @if($stage['status'] === 'completed')
                                        <i class="fas fa-check"></i>
                                    @elseif($stage['status'] === 'in_progress')
                                        <i class="{{ $stage['icon'] }}"></i>
                                    @else
                                        <span class="text-sm font-semibold">{{ $index + 1 }}</span>
                                    @endif
                                </div>
                                @if($index < count($journey) - 1)
                                    <div class="w-0.5 h-16 
                                        @if($stage['status'] === 'completed')
                                            bg-green-500
                                        @else
                                            bg-gray-200
                                        @endif">
                                    </div>
                                @endif
                            </div>

                            <!-- Stage Content -->
                            <div class="flex-1 pt-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-lg font-medium 
                                        @if($stage['status'] === 'completed')
                                            text-green-700
                                        @elseif($stage['status'] === 'in_progress')
                                            text-blue-700
                                        @else
                                            text-gray-500
                                        @endif">
                                        {{ $stage['name'] }}
                                    </h3>
                                    <span class="text-xs px-2 py-1 rounded-full 
                                        @if($stage['status'] === 'completed')
                                            bg-green-100 text-green-700
                                        @elseif($stage['status'] === 'in_progress')
                                            bg-blue-100 text-blue-700
                                        @else
                                            bg-gray-100 text-gray-500
                                        @endif">
                                        @if($stage['status'] === 'completed')
                                            Completed
                                        @elseif($stage['status'] === 'in_progress')
                                            In Progress
                                        @else
                                            Pending
                                        @endif
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mb-2">Module {{ $stage['module'] }}</p>
                                
                                @if($stage['completed_at'])
                                    <p class="text-xs text-gray-400">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ \Carbon\Carbon::parse($stage['completed_at'])->format('M d, Y h:i A') }}
                                    </p>
                                @endif

                                @if(count($stage['details']) > 0)
                                    <div class="mt-2 p-3 bg-gray-50 rounded-lg">
                                        <dl class="grid grid-cols-2 gap-2 text-sm">
                                            @foreach($stage['details'] as $key => $value)
                                                @if(!is_array($value) && $value)
                                                    <dt class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                                    <dd class="text-gray-900">{{ $value }}</dd>
                                                @endif
                                            @endforeach
                                        </dl>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Milestones Panel -->
        <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Key Milestones</h2>
                
                <div class="space-y-4">
                    @foreach($milestones as $milestone)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div class="flex items-center">
                                @if($milestone['completed'])
                                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                @else
                                    <i class="far fa-circle text-gray-300 mr-3"></i>
                                @endif
                                <span class="{{ $milestone['completed'] ? 'text-gray-900' : 'text-gray-400' }}">
                                    {{ $milestone['name'] }}
                                </span>
                            </div>
                            @if($milestone['date'])
                                <span class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($milestone['date'])->format('M d') }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Candidate Info Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Candidate Info</h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">TheLeap ID</span>
                        <span class="font-medium">{{ $candidate->btevta_id ?? 'Not Assigned' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">CNIC</span>
                        <span class="font-medium">{{ $candidate->cnic }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Campus</span>
                        <span class="font-medium">{{ $candidate->campus?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Trade</span>
                        <span class="font-medium">{{ $candidate->trade?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Batch</span>
                        <span class="font-medium">{{ $candidate->batch?->name ?? 'Not Assigned' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">OEP</span>
                        <span class="font-medium">{{ $candidate->oep?->name ?? 'Not Assigned' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Listed On</span>
                        <span class="font-medium">{{ $candidate->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
