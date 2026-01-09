@extends('layouts.app')

@section('title', 'Activity Log Statistics')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Activity Log Statistics</h1>
            <p class="text-gray-600 mt-1">System activity overview and analytics</p>
        </div>
        <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Logs
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-6">
        <form method="GET" action="{{ route('activity-logs.statistics') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date', $fromDate instanceof \Carbon\Carbon ? $fromDate->format('Y-m-d') : $fromDate) }}"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date', $toDate instanceof \Carbon\Carbon ? $toDate->format('Y-m-d') : $toDate) }}"
                       class="form-input">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter mr-2"></i>Apply Filter
            </button>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Activities</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_activities']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-history text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['by_user']->count() }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-users text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Log Types</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['by_log_name']->count() }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-tags text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Entity Types</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['by_subject_type']->count() }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-cubes text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Activity by Log Type -->
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-tags mr-2 text-gray-500"></i>Activity by Log Type
                </h2>
            </div>
            @if($stats['by_log_name']->isNotEmpty())
                <div class="space-y-3">
                    @foreach($stats['by_log_name'] as $logName)
                        @php
                            $percentage = $stats['total_activities'] > 0 ? ($logName->count / $stats['total_activities']) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $logName->log_name ?? 'default' }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($logName->count) }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>No activity data available</p>
                </div>
            @endif
        </div>

        <!-- Activity by User -->
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-users mr-2 text-gray-500"></i>Top Active Users
                </h2>
            </div>
            @if($stats['by_user']->isNotEmpty())
                <div class="space-y-3">
                    @foreach($stats['by_user'] as $userData)
                        @php
                            $percentage = $stats['total_activities'] > 0 ? ($userData->count / $stats['total_activities']) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $userData->name }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($userData->count) }} actions</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-user-slash text-3xl mb-2"></i>
                    <p>No user activity data</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Activity by Subject Type -->
    <div class="card mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">
                <i class="fas fa-cubes mr-2 text-gray-500"></i>Activity by Entity Type
            </h2>
        </div>
        @if($stats['by_subject_type']->isNotEmpty())
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach($stats['by_subject_type'] as $subjectType)
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-full mx-auto mb-2 flex items-center justify-center">
                            <i class="fas fa-cube text-purple-600"></i>
                        </div>
                        <p class="font-semibold text-gray-900">{{ number_format($subjectType->count) }}</p>
                        <p class="text-sm text-gray-600">{{ $subjectType->subject_type }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2"></i>
                <p>No entity data available</p>
            </div>
        @endif
    </div>

    <!-- Recent Activities -->
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">
                <i class="fas fa-clock mr-2 text-gray-500"></i>Recent Activities
            </h2>
            <a href="{{ route('activity-logs.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        @if($stats['recent_activities']->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>User</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['recent_activities'] as $activity)
                        <tr>
                            <td>
                                <span class="text-sm">{{ $activity->description }}</span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-600">{{ $activity->causer?->name ?? 'System' }}</span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-6 text-gray-500">
                <i class="fas fa-history text-3xl mb-2"></i>
                <p>No recent activities</p>
            </div>
        @endif
    </div>
</div>
@endsection
