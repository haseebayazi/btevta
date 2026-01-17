@extends('layouts.app')
@section('title', 'Trainer Performance Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Trainer Performance Report</h1>
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
            <p class="text-sm text-green-800">Avg Pass Rate</p>
            <p class="text-3xl font-bold text-green-900">{{ round($stats['avg_pass_rate'], 1) }}%</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Avg Attendance Rate</p>
            <p class="text-3xl font-bold text-purple-900">{{ round($stats['avg_attendance_rate'], 1) }}%</p>
        </div>
        <div class="card bg-indigo-50">
            <p class="text-sm text-indigo-800">Total Students Taught</p>
            <p class="text-3xl font-bold text-indigo-900">{{ number_format($stats['total_students_taught']) }}</p>
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
                        <th class="px-4 py-3 text-left">Instructor Name</th>
                        <th class="px-4 py-3 text-left">Campus</th>
                        <th class="px-4 py-3 text-center">Batches</th>
                        <th class="px-4 py-3 text-center">Students</th>
                        <th class="px-4 py-3 text-center">Attendance Rate</th>
                        <th class="px-4 py-3 text-center">Pass Rate</th>
                        <th class="px-4 py-3 text-center">Avg Score</th>
                        <th class="px-4 py-3 text-center">Performance</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($instructors as $instructor)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('reports.trainer-detail', $instructor->id) }}" class="font-medium text-blue-600 hover:text-blue-900 hover:underline">
                                {{ $instructor->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $instructor->campus?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ $instructor->total_batches }}</td>
                        <td class="px-4 py-3 text-center">{{ $instructor->total_students }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 rounded-full h-2" style="width: {{ $instructor->attendance_rate }}%"></div>
                                </div>
                                <span class="text-sm">{{ $instructor->attendance_rate }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 rounded-full h-2" style="width: {{ $instructor->pass_rate }}%"></div>
                                </div>
                                <span class="text-sm">{{ $instructor->pass_rate }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">{{ round($instructor->avg_score, 1) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $avgPerformance = ($instructor->pass_rate + $instructor->attendance_rate) / 2;
                            @endphp
                            @if($avgPerformance >= 80)
                                <span class="badge badge-success">Excellent</span>
                            @elseif($avgPerformance >= 60)
                                <span class="badge badge-info">Good</span>
                            @elseif($avgPerformance >= 40)
                                <span class="badge badge-warning">Fair</span>
                            @else
                                <span class="badge badge-danger">Needs Improvement</span>
                            @endif
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
</div>
@endsection
