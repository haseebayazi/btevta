@extends('layouts.app')

@section('title', 'Instructor Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Instructor Banner -->
    <div class="bg-gradient-to-r from-teal-600 to-teal-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Instructor Dashboard</h1>
                <p class="text-teal-100 mt-1">Training Management Portal</p>
            </div>
            <div class="text-right hidden md:block">
                <p class="text-sm text-teal-100">{{ now()->format('l, F d, Y') }}</p>
                <p class="text-sm text-teal-100">{{ now()->format('h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-lg font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-600">Instructor / Trainer</p>
    </div>

    <!-- Instructor Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">My Batches</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ count($roleData['current_batches'] ?? []) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-chalkboard-teacher text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Students</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($roleData['total_students'] ?? 0) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-users text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Today's Classes</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ count($roleData['todays_schedule'] ?? []) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-calendar-day text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pending Assessments</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($roleData['pending_assessments'] ?? 0) }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-clipboard-list text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    @if(!empty($roleData['todays_schedule']) && count($roleData['todays_schedule']) > 0)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Today's Schedule</h3>
        <div class="space-y-3">
            @foreach($roleData['todays_schedule'] as $schedule)
            <div class="flex items-center justify-between p-4 bg-teal-50 border border-teal-200 rounded-lg">
                <div class="flex items-center">
                    <div class="bg-teal-100 rounded-full p-3 mr-4">
                        <i class="fas fa-book text-teal-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $schedule->batch->batch_code ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-600">{{ $schedule->topic ?? 'General Training' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-teal-700">{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') : 'N/A' }}</p>
                    <p class="text-sm text-gray-500">{{ $schedule->duration ?? 0 }} mins</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Today's Schedule</h3>
        <p class="text-center text-gray-500 py-4">No classes scheduled for today</p>
    </div>
    @endif

    <!-- My Batches -->
    @if(!empty($roleData['current_batches']) && count($roleData['current_batches']) > 0)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">My Active Batches</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($roleData['current_batches'] as $batch)
            <div class="border rounded-lg p-4 hover:border-teal-500 transition">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900">{{ $batch->batch_code }}</h4>
                    <span class="badge badge-success">Active</span>
                </div>
                <p class="text-sm text-gray-600">{{ $batch->trade->name ?? 'N/A' }}</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-users mr-1"></i>{{ $batch->candidates_count ?? 0 }} students
                    </span>
                    <a href="{{ route('training.attendance.batch', $batch) }}" class="text-teal-600 hover:underline text-sm">Mark Attendance</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">My Active Batches</h3>
        <p class="text-center text-gray-500 py-4">No active batches assigned</p>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('training.attendance.index') }}" class="bg-white hover:bg-teal-50 border-2 border-teal-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-clipboard-check text-teal-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Mark Attendance</h4>
            <p class="text-sm text-gray-600 mt-1">Record daily attendance</p>
        </a>
        <a href="{{ route('training.assessments.index') }}" class="bg-white hover:bg-blue-50 border-2 border-blue-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-file-alt text-blue-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Assessments</h4>
            <p class="text-sm text-gray-600 mt-1">Create & grade tests</p>
        </a>
        <a href="{{ route('training.schedule.index') }}" class="bg-white hover:bg-purple-50 border-2 border-purple-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-calendar-alt text-purple-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">Schedule</h4>
            <p class="text-sm text-gray-600 mt-1">View training schedule</p>
        </a>
        <a href="{{ route('reports.trainer-performance') }}" class="bg-white hover:bg-green-50 border-2 border-green-200 rounded-lg p-6 text-center transition group">
            <i class="fas fa-chart-bar text-green-600 text-3xl mb-3 group-hover:scale-110 transition"></i>
            <h4 class="font-semibold text-gray-900">My Performance</h4>
            <p class="text-sm text-gray-600 mt-1">View stats & reports</p>
        </a>
    </div>

</div>
@endsection
