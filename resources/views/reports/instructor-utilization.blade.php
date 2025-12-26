@extends('layouts.app')
@section('title', 'Instructor Utilization Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Instructor Utilization Report</h1>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Reports
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Instructors</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total_instructors'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Active Batches</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['total_active_batches'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Current Students</p>
            <p class="text-3xl font-bold text-purple-900">{{ number_format($stats['total_current_students']) }}</p>
        </div>
        <div class="card bg-indigo-50">
            <p class="text-sm text-indigo-800">Avg Batch Utilization</p>
            <p class="text-3xl font-bold text-indigo-900">{{ $stats['avg_batch_utilization'] }}%</p>
        </div>
    </div>

    <!-- Utilization Distribution -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-red-50 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-800">Overloaded</p>
                    <p class="text-2xl font-bold text-red-900">{{ $stats['overloaded'] }}</p>
                </div>
                <i class="fas fa-exclamation-circle text-red-500 text-2xl"></i>
            </div>
        </div>
        <div class="card bg-green-50 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-800">Optimal</p>
                    <p class="text-2xl font-bold text-green-900">{{ $stats['optimal'] }}</p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            </div>
        </div>
        <div class="card bg-yellow-50 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-800">Underutilized</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ $stats['underutilized'] }}</p>
                </div>
                <i class="fas fa-minus-circle text-yellow-500 text-2xl"></i>
            </div>
        </div>
        <div class="card bg-gray-50 border-l-4 border-gray-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-800">Available</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['available'] }}</p>
                </div>
                <i class="fas fa-user-clock text-gray-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Campus</label>
                <select name="campus_id" class="form-input">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['campus_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <!-- Instructors Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Instructor</th>
                        <th class="px-4 py-3 text-left">Campus</th>
                        <th class="px-4 py-3 text-center">Active Batches</th>
                        <th class="px-4 py-3 text-center">Students</th>
                        <th class="px-4 py-3 text-center">Hours/Week</th>
                        <th class="px-4 py-3 text-center">Batch Util.</th>
                        <th class="px-4 py-3 text-center">Hours Util.</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($instructors as $instructor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $instructor->name }}</td>
                        <td class="px-4 py-3">{{ $instructor->campus?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold">{{ $instructor->active_batches_count }}</span>
                            <span class="text-gray-500 text-sm">/ {{ $instructor->max_batches }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $instructor->current_students }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold">{{ $instructor->scheduled_hours_week }}</span>
                            <span class="text-gray-500 text-sm">/ {{ $instructor->max_hours }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="{{ $instructor->batch_utilization >= 90 ? 'bg-red-600' : ($instructor->batch_utilization >= 70 ? 'bg-green-600' : 'bg-yellow-500') }} rounded-full h-2" style="width: {{ min(100, $instructor->batch_utilization) }}%"></div>
                                </div>
                                <span class="text-sm">{{ $instructor->batch_utilization }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="{{ $instructor->hours_utilization >= 90 ? 'bg-red-600' : ($instructor->hours_utilization >= 70 ? 'bg-green-600' : 'bg-yellow-500') }} rounded-full h-2" style="width: {{ min(100, $instructor->hours_utilization) }}%"></div>
                                </div>
                                <span class="text-sm">{{ $instructor->hours_utilization }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @switch($instructor->utilization_status)
                                @case('overloaded')
                                    <span class="badge badge-danger">Overloaded</span>
                                    @break
                                @case('optimal')
                                    <span class="badge badge-success">Optimal</span>
                                    @break
                                @case('underutilized')
                                    <span class="badge badge-warning">Underutilized</span>
                                    @break
                                @default
                                    <span class="badge badge-secondary">Available</span>
                            @endswitch
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No instructors found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-6">
        <h4 class="font-bold text-gray-900 mb-3">Utilization Status Legend</h4>
        <div class="grid md:grid-cols-4 gap-4 text-sm">
            <div class="flex items-center">
                <span class="badge badge-danger mr-2">Overloaded</span>
                <span class="text-gray-600">90%+ average utilization</span>
            </div>
            <div class="flex items-center">
                <span class="badge badge-success mr-2">Optimal</span>
                <span class="text-gray-600">70-89% average utilization</span>
            </div>
            <div class="flex items-center">
                <span class="badge badge-warning mr-2">Underutilized</span>
                <span class="text-gray-600">40-69% average utilization</span>
            </div>
            <div class="flex items-center">
                <span class="badge badge-secondary mr-2">Available</span>
                <span class="text-gray-600">Below 40% utilization</span>
            </div>
        </div>
    </div>
</div>
@endsection
