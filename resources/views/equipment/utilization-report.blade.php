@extends('layouts.app')

@section('title', 'Equipment Utilization Report - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Equipment Utilization Report</h1>
            <p class="text-gray-600 mt-1">Analyze equipment usage patterns and efficiency across campuses</p>
        </div>
        <a href="{{ route('equipment.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Equipment
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('equipment.utilization-report') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="campus_id" class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" id="campus_id" class="form-select w-full">
                    <option value="">All Campuses</option>
                    @foreach($campuses ?? [] as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <input type="month" name="month" id="month" value="{{ request('month', now()->format('Y-m')) }}" class="form-input w-full">
            </div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" id="category" class="form-select w-full">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\CampusEquipment::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-chart-bar mr-2"></i>Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-medium">Total Equipment</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($summary['total_equipment'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-medium">Total Training Hours</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($summary['total_training_hours'] ?? 0, 1) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <p class="text-gray-600 text-sm font-medium">Average Utilization</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ number_format($summary['average_utilization'] ?? 0, 1) }}%</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm font-medium">Students Trained</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($summary['total_students'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Utilization by Campus -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Utilization by Campus</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equipment Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Training Hours</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilization Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($campusUtilization ?? [] as $campus)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $campus['name'] }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ number_format($campus['equipment_count']) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ number_format($campus['training_hours'], 1) }} hrs</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="{{ $campus['utilization_rate'] >= 70 ? 'bg-green-500' : ($campus['utilization_rate'] >= 40 ? 'bg-yellow-500' : 'bg-red-500') }} h-2 rounded-full" style="width: {{ min($campus['utilization_rate'], 100) }}%"></div>
                                </div>
                                <span class="font-medium">{{ number_format($campus['utilization_rate'], 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($campus['utilization_rate'] >= 70)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Optimal
                                </span>
                            @elseif($campus['utilization_rate'] >= 40)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Moderate
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Underutilized
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Utilization by Category -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Utilization by Equipment Category</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($categoryUtilization ?? [] as $category)
            <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">{{ \App\Models\CampusEquipment::CATEGORIES[$category['category']] ?? $category['category'] }}</h4>
                    <span class="text-sm text-gray-500">{{ $category['count'] }} items</span>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Utilization</span>
                        <span class="font-medium {{ $category['utilization'] >= 70 ? 'text-green-600' : ($category['utilization'] >= 40 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($category['utilization'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="{{ $category['utilization'] >= 70 ? 'bg-green-500' : ($category['utilization'] >= 40 ? 'bg-yellow-500' : 'bg-red-500') }} h-2 rounded-full" style="width: {{ min($category['utilization'], 100) }}%"></div>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    {{ number_format($category['training_hours'], 1) }} training hours
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Top/Bottom Utilized Equipment -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Utilized -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-arrow-up text-green-500 mr-2"></i>Top Utilized Equipment
            </h3>
            <div class="space-y-3">
                @forelse($topUtilized ?? [] as $equipment)
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $equipment->name }}</p>
                        <p class="text-sm text-gray-500">{{ $equipment->campus->name ?? 'N/A' }} | {{ $equipment->equipment_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-600">{{ $equipment->getUtilizationRate() }}%</p>
                        <p class="text-xs text-gray-500">utilization</p>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No data available</p>
                @endforelse
            </div>
        </div>

        <!-- Bottom Utilized -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-arrow-down text-red-500 mr-2"></i>Underutilized Equipment
            </h3>
            <div class="space-y-3">
                @forelse($bottomUtilized ?? [] as $equipment)
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $equipment->name }}</p>
                        <p class="text-sm text-gray-500">{{ $equipment->campus->name ?? 'N/A' }} | {{ $equipment->equipment_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-red-600">{{ $equipment->getUtilizationRate() }}%</p>
                        <p class="text-xs text-gray-500">utilization</p>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    @if(!empty($recommendations ?? []))
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Recommendations
        </h3>
        <div class="space-y-3">
            @foreach($recommendations as $recommendation)
            <div class="flex items-start p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg">
                <i class="fas fa-info-circle text-yellow-600 mt-0.5 mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">{{ $recommendation['title'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ $recommendation['description'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
