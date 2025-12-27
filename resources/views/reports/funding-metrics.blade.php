@extends('layouts.app')

@section('title', 'Funding Metrics & KPIs - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Funding Metrics & KPI Dashboard</h1>
            <p class="text-gray-600 mt-1">Performance-based funding metrics by campus</p>
        </div>
        <div class="flex gap-3">
            <form action="{{ route('reports.calculate-kpis') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-2"></i>Recalculate KPIs
                </button>
            </form>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Reports
            </a>
        </div>
    </div>

    <!-- Period Filter -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('reports.funding-metrics') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select name="year" id="year" class="form-select w-full">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="quarter" class="block text-sm font-medium text-gray-700 mb-1">Quarter</label>
                <select name="quarter" id="quarter" class="form-select w-full">
                    <option value="">All Quarters</option>
                    <option value="1" {{ request('quarter') == '1' ? 'selected' : '' }}>Q1 (Jan-Mar)</option>
                    <option value="2" {{ request('quarter') == '2' ? 'selected' : '' }}>Q2 (Apr-Jun)</option>
                    <option value="3" {{ request('quarter') == '3' ? 'selected' : '' }}>Q3 (Jul-Sep)</option>
                    <option value="4" {{ request('quarter') == '4' ? 'selected' : '' }}>Q4 (Oct-Dec)</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Overall Performance Summary -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold opacity-90">Overall Performance Score</h2>
                <div class="flex items-baseline mt-2">
                    <span class="text-6xl font-bold">{{ number_format($overallScore ?? 0, 1) }}</span>
                    <span class="text-2xl font-semibold ml-2 opacity-90">/ 100</span>
                </div>
                <p class="mt-2 opacity-80">
                    Grade:
                    @if(($overallScore ?? 0) >= 90) A+
                    @elseif(($overallScore ?? 0) >= 80) A
                    @elseif(($overallScore ?? 0) >= 70) B
                    @elseif(($overallScore ?? 0) >= 60) C
                    @else D
                    @endif
                </p>
            </div>
            <div class="text-right hidden md:block">
                <div class="text-6xl opacity-30">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($kpiCategories ?? [] as $category)
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 {{ $category['color'] ?? 'border-blue-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ $category['name'] }}</h3>
                <span class="text-sm text-gray-500">Weight: {{ $category['weight'] }}%</span>
            </div>
            <div class="text-3xl font-bold {{ $category['score'] >= 80 ? 'text-green-600' : ($category['score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ number_format($category['score'], 1) }}%
            </div>
            <div class="mt-3">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="{{ $category['score'] >= 80 ? 'bg-green-500' : ($category['score'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') }} h-2 rounded-full transition-all" style="width: {{ min($category['score'], 100) }}%"></div>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">{{ $category['description'] ?? '' }}</p>
        </div>
        @endforeach
    </div>

    <!-- Campus Performance Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Campus Performance Rankings</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrollment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Graduation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compliance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overall Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Funding Tier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($campusPerformance ?? [] as $index => $campus)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if($index < 3)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : ($index === 1 ? 'bg-gray-300 text-gray-700' : 'bg-amber-600 text-amber-100') }} font-bold">
                                    {{ $index + 1 }}
                                </span>
                            @else
                                <span class="text-gray-600 font-medium">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $campus['name'] }}</td>
                        <td class="px-6 py-4">
                            <span class="{{ $campus['enrollment_rate'] >= 80 ? 'text-green-600' : ($campus['enrollment_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($campus['enrollment_rate'], 1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="{{ $campus['graduation_rate'] >= 80 ? 'text-green-600' : ($campus['graduation_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($campus['graduation_rate'], 1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="{{ $campus['placement_rate'] >= 80 ? 'text-green-600' : ($campus['placement_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($campus['placement_rate'], 1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="{{ $campus['compliance_rate'] >= 80 ? 'text-green-600' : ($campus['compliance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($campus['compliance_rate'], 1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <span class="text-lg font-bold {{ $campus['overall_score'] >= 80 ? 'text-green-600' : ($campus['overall_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($campus['overall_score'], 1) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $tierColors = [
                                    'A' => 'bg-green-100 text-green-800',
                                    'B' => 'bg-blue-100 text-blue-800',
                                    'C' => 'bg-yellow-100 text-yellow-800',
                                    'D' => 'bg-red-100 text-red-800',
                                ];
                                $tier = $campus['overall_score'] >= 80 ? 'A' : ($campus['overall_score'] >= 70 ? 'B' : ($campus['overall_score'] >= 60 ? 'C' : 'D'));
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $tierColors[$tier] }}">
                                Tier {{ $tier }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- KPI Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Enrollment Metrics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-user-plus text-blue-600 mr-2"></i>Enrollment Metrics
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Total Enrolled</span>
                    <span class="font-bold text-gray-900">{{ number_format($enrollmentMetrics['total_enrolled'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Target Enrollment</span>
                    <span class="font-bold text-gray-900">{{ number_format($enrollmentMetrics['target'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Achievement Rate</span>
                    <span class="font-bold {{ ($enrollmentMetrics['rate'] ?? 0) >= 100 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ number_format($enrollmentMetrics['rate'] ?? 0, 1) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Dropout Rate</span>
                    <span class="font-bold {{ ($enrollmentMetrics['dropout_rate'] ?? 0) <= 10 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($enrollmentMetrics['dropout_rate'] ?? 0, 1) }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Placement Metrics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-briefcase text-green-600 mr-2"></i>Placement Metrics
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Successfully Deployed</span>
                    <span class="font-bold text-gray-900">{{ number_format($placementMetrics['deployed'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Awaiting Deployment</span>
                    <span class="font-bold text-yellow-600">{{ number_format($placementMetrics['awaiting'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Placement Rate</span>
                    <span class="font-bold {{ ($placementMetrics['rate'] ?? 0) >= 80 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ number_format($placementMetrics['rate'] ?? 0, 1) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Avg. Time to Placement</span>
                    <span class="font-bold text-gray-900">{{ $placementMetrics['avg_days'] ?? 0 }} days</span>
                </div>
            </div>
        </div>

        <!-- Compliance Metrics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-clipboard-check text-purple-600 mr-2"></i>Compliance Metrics
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Document Compliance</span>
                    <span class="font-bold {{ ($complianceMetrics['document_rate'] ?? 0) >= 90 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ number_format($complianceMetrics['document_rate'] ?? 0, 1) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">90-Day Report Rate</span>
                    <span class="font-bold {{ ($complianceMetrics['ninety_day_rate'] ?? 0) >= 90 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ number_format($complianceMetrics['ninety_day_rate'] ?? 0, 1) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Complaint SLA Rate</span>
                    <span class="font-bold {{ ($complianceMetrics['sla_rate'] ?? 0) >= 90 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ number_format($complianceMetrics['sla_rate'] ?? 0, 1) }}%
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="text-gray-700">Attendance Rate</span>
                    <span class="font-bold {{ ($complianceMetrics['attendance_rate'] ?? 0) >= 80 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ number_format($complianceMetrics['attendance_rate'] ?? 0, 1) }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Financial Impact -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fas fa-dollar-sign text-yellow-600 mr-2"></i>Funding Impact Projection
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                    <span class="text-gray-700">Base Funding Allocation</span>
                    <span class="font-bold text-gray-900">PKR {{ number_format($fundingImpact['base_allocation'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 {{ ($fundingImpact['performance_bonus'] ?? 0) > 0 ? 'bg-green-50' : 'bg-red-50' }} rounded">
                    <span class="text-gray-700">Performance Bonus/Penalty</span>
                    <span class="font-bold {{ ($fundingImpact['performance_bonus'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($fundingImpact['performance_bonus'] ?? 0) >= 0 ? '+' : '' }}PKR {{ number_format($fundingImpact['performance_bonus'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded border-2 border-blue-200">
                    <span class="text-gray-700 font-semibold">Projected Total Funding</span>
                    <span class="font-bold text-blue-700 text-lg">PKR {{ number_format($fundingImpact['projected_total'] ?? 0) }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    * Performance bonus/penalty calculated based on KPI achievement vs. targets
                </p>
            </div>
        </div>
    </div>

    <!-- Trend Chart Placeholder -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-chart-line text-blue-600 mr-2"></i>Performance Trend (Last 6 Months)
        </h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
            <div class="text-center text-gray-500">
                <i class="fas fa-chart-area text-4xl mb-3"></i>
                <p>Chart visualization will be displayed here</p>
                <p class="text-sm">Requires Chart.js integration</p>
            </div>
        </div>
    </div>

</div>
@endsection
