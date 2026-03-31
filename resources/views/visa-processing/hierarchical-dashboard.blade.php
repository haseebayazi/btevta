@extends('layouts.app')

@section('title', 'Visa Processing - Hierarchical Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ activeTab: 'all', campusFilter: '{{ request('campus_id', '') }}' }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('visa-processing.index') }}" class="hover:text-blue-600">Visa Processing</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Hierarchical Dashboard</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900">Visa Processing Pipeline</h1>
            <p class="text-gray-500 text-sm mt-1">Stage-by-stage overview of all active visa processes</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3">
            <a href="{{ route('visa-processing.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                <i class="fas fa-list mr-1"></i> List View
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-check text-blue-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-blue-800">Scheduled</span>
            </div>
            <p class="text-2xl font-bold text-blue-900">{{ $dashboard['counts']['scheduled'] }}</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-yellow-800">Pending</span>
            </div>
            <p class="text-2xl font-bold text-yellow-900">{{ $dashboard['counts']['pending'] }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-green-800">Passed</span>
            </div>
            <p class="text-2xl font-bold text-green-900">{{ $dashboard['counts']['passed'] }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check text-gray-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">Done</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $dashboard['counts']['done'] }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-red-800">Failed</span>
            </div>
            <p class="text-2xl font-bold text-red-900">{{ $dashboard['counts']['failed'] }}</p>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="flex flex-wrap border-b px-4">
            <button @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                All ({{ array_sum($dashboard['counts']) }})
            </button>
            <button @click="activeTab = 'scheduled'"
                :class="activeTab === 'scheduled' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                <i class="fas fa-calendar-check mr-1"></i> Scheduled ({{ $dashboard['counts']['scheduled'] }})
            </button>
            <button @click="activeTab = 'pending'"
                :class="activeTab === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                <i class="fas fa-clock mr-1"></i> Pending ({{ $dashboard['counts']['pending'] }})
            </button>
            <button @click="activeTab = 'passed'"
                :class="activeTab === 'passed' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                <i class="fas fa-check-circle mr-1"></i> Passed ({{ $dashboard['counts']['passed'] }})
            </button>
            <button @click="activeTab = 'failed'"
                :class="activeTab === 'failed' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                <i class="fas fa-times-circle mr-1"></i> Failed ({{ $dashboard['counts']['failed'] }})
            </button>
        </div>

        <div class="p-4">
            {{-- Scheduled --}}
            <div x-show="activeTab === 'all' || activeTab === 'scheduled'" x-cloak>
                @if($dashboard['items']['scheduled']->isNotEmpty())
                <h3 class="text-lg font-semibold text-blue-800 mb-3">
                    <i class="fas fa-calendar-check mr-2"></i>Scheduled Appointments
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Center</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dashboard['items']['scheduled'] as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.show', $item['candidate']) }}" class="text-blue-600 hover:underline font-medium">
                                        {{ $item['candidate']->name }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ $item['candidate']->btevta_id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                        <i class="{{ $item['icon'] }}"></i> {{ $item['stage_name'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $item['details']->appointmentDate ?? '-' }}
                                    @if($item['details']->appointmentTime)
                                        <span class="text-gray-400">at</span> {{ $item['details']->appointmentTime }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $item['details']->center ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.stage-details', [$item['visa_process_id'], $item['stage']]) }}" class="text-blue-600 hover:underline text-sm">
                                        <i class="fas fa-eye mr-1"></i> Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-calendar-check text-4xl mb-2"></i>
                    <p>No scheduled appointments</p>
                </div>
                @endif
            </div>

            {{-- Pending --}}
            <div x-show="activeTab === 'all' || activeTab === 'pending'" x-cloak>
                @if($dashboard['items']['pending']->isNotEmpty())
                <h3 class="text-lg font-semibold text-yellow-800 mb-3">
                    <i class="fas fa-clock mr-2"></i>Pending Stages
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-yellow-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trade</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dashboard['items']['pending'] as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.show', $item['candidate']) }}" class="text-blue-600 hover:underline font-medium">
                                        {{ $item['candidate']->name }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ $item['candidate']->btevta_id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">
                                        <i class="{{ $item['icon'] }}"></i> {{ $item['stage_name'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $item['candidate']->campus->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $item['candidate']->trade->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.stage-details', [$item['visa_process_id'], $item['stage']]) }}" class="text-blue-600 hover:underline text-sm">
                                        <i class="fas fa-edit mr-1"></i> Manage
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-clock text-4xl mb-2"></i>
                    <p>No pending stages</p>
                </div>
                @endif
            </div>

            {{-- Passed --}}
            <div x-show="activeTab === 'all' || activeTab === 'passed'" x-cloak>
                @if($dashboard['items']['passed']->isNotEmpty())
                <h3 class="text-lg font-semibold text-green-800 mb-3">
                    <i class="fas fa-check-circle mr-2"></i>Passed Stages
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Evidence</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dashboard['items']['passed'] as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.show', $item['candidate']) }}" class="text-blue-600 hover:underline font-medium">
                                        {{ $item['candidate']->name }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ $item['candidate']->btevta_id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                        <i class="fas fa-check-circle"></i> {{ $item['stage_name'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($item['details']->notes ?? '-', 50) }}</td>
                                <td class="px-4 py-3">
                                    @if($item['details']->hasEvidence())
                                        <span class="text-green-600 text-sm"><i class="fas fa-file-alt mr-1"></i> Uploaded</span>
                                    @else
                                        <span class="text-gray-400 text-sm">No evidence</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.stage-details', [$item['visa_process_id'], $item['stage']]) }}" class="text-blue-600 hover:underline text-sm">
                                        <i class="fas fa-eye mr-1"></i> Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                    <p>No passed stages yet</p>
                </div>
                @endif
            </div>

            {{-- Failed --}}
            <div x-show="activeTab === 'all' || activeTab === 'failed'" x-cloak>
                @if($dashboard['items']['failed']->isNotEmpty())
                <h3 class="text-lg font-semibold text-red-800 mb-3">
                    <i class="fas fa-times-circle mr-2"></i>Failed/Refused
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dashboard['items']['failed'] as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.show', $item['candidate']) }}" class="text-blue-600 hover:underline font-medium">
                                        {{ $item['candidate']->name }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ $item['candidate']->btevta_id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                                        <i class="fas fa-times-circle"></i> {{ $item['stage_name'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($item['details']->notes ?? '-', 50) }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('visa-processing.stage-details', [$item['visa_process_id'], $item['stage']]) }}" class="text-blue-600 hover:underline text-sm">
                                        <i class="fas fa-eye mr-1"></i> Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                    <p>No failed stages</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
