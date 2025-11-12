@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Candidates</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $reportStats['total_candidates'] ?? 0 }}</p>
                </div>
                <i class="fas fa-users text-blue-200 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Completed Process</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $reportStats['completed_process'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-green-200 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">In Process</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $reportStats['in_process'] ?? 0 }}</p>
                </div>
                <i class="fas fa-hourglass-half text-yellow-200 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Rejected</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ $reportStats['rejected'] ?? 0 }}</p>
                </div>
                <i class="fas fa-times-circle text-red-200 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Report Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('reports.campus-performance') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">Campus Performance</h3>
                    <p class="text-sm text-gray-600 mt-1">Comparative analysis of all campuses</p>
                </div>
                <i class="fas fa-building text-blue-600 text-3xl"></i>
            </div>
        </a>

        <a href="{{ route('reports.training-statistics') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">Training Statistics</h3>
                    <p class="text-sm text-gray-600 mt-1">Training batch statistics</p>
                </div>
                <i class="fas fa-layer-group text-green-600 text-3xl"></i>
            </div>
        </a>

        <a href="{{ route('candidates.index') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">Candidate Records</h3>
                    <p class="text-sm text-gray-600 mt-1">View all candidate records</p>
                </div>
                <i class="fas fa-user text-purple-600 text-3xl"></i>
            </div>
        </a>

        <a href="{{ route('reports.visa-timeline') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">Visa Timeline</h3>
                    <p class="text-sm text-gray-600 mt-1">Visa processing tracking</p>
                </div>
                <i class="fas fa-passport text-indigo-600 text-3xl"></i>
            </div>
        </a>

        <a href="{{ route('reports.training-statistics') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">Training Statistics</h3>
                    <p class="text-sm text-gray-600 mt-1">Training performance metrics</p>
                </div>
                <i class="fas fa-chart-bar text-orange-600 text-3xl"></i>
            </div>
        </a>

        <a href="{{ route('reports.custom-report') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-lg">Custom Report</h3>
                    <p class="text-sm text-gray-600 mt-1">Build your own report</p>
                </div>
                <i class="fas fa-cogs text-gray-600 text-3xl"></i>
            </div>
        </a>
    </div>
</div>
@endsection