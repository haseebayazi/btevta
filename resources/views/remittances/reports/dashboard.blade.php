@extends('layouts.app')

@section('title', 'Remittance Analytics Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Analytics Dashboard</h1>
            <p class="text-gray-600 mt-1">Comprehensive remittance analytics and insights</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittance.reports.export', ['type' => 'dashboard', 'format' => 'excel']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </a>
            <a href="{{ route('remittance.reports.export', ['type' => 'dashboard', 'format' => 'pdf']) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg inline-flex items-center" target="_blank">
                <i class="fas fa-file-pdf mr-2"></i>
                Export PDF
            </a>
            <a href="{{ route('remittances.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to List
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Remittances -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Remittances</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_remittances']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">All time</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-money-bill-transfer text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Amount</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_amount'], 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">PKR</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-coins text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Average Amount -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Average Amount</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['average_amount'], 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">PKR per transfer</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-chart-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Candidates -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Candidates</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_candidates']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Sending remittances</p>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-users text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Current Year -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-calendar-alt mr-2"></i>{{ date('Y') }} Performance
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Count</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['current_year_count']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Total Amount</span>
                    <span class="font-semibold text-gray-900">PKR {{ number_format($stats['current_year_amount'], 0) }}</span>
                </div>
                <div class="pt-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">YoY Growth</span>
                        <span class="font-semibold {{ $stats['year_over_year_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            <i class="fas fa-arrow-{{ $stats['year_over_year_growth'] >= 0 ? 'up' : 'down' }} mr-1"></i>
                            {{ number_format(abs($stats['year_over_year_growth']), 2) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Month -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-calendar-week mr-2"></i>{{ date('F Y') }} Performance
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Count</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['current_month_count']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Total Amount</span>
                    <span class="font-semibold text-gray-900">PKR {{ number_format($stats['current_month_amount'], 0) }}</span>
                </div>
                <div class="pt-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">MoM Growth</span>
                        <span class="font-semibold {{ $stats['month_over_month_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            <i class="fas fa-arrow-{{ $stats['month_over_month_growth'] >= 0 ? 'up' : 'down' }} mr-1"></i>
                            {{ number_format(abs($stats['month_over_month_growth']), 2) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proof Compliance -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-check-circle mr-2"></i>Proof Compliance
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">With Proof</span>
                    <span class="font-semibold text-green-600">{{ number_format($stats['with_proof']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Without Proof</span>
                    <span class="font-semibold text-red-600">{{ number_format($stats['without_proof']) }}</span>
                </div>
                <div class="pt-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Compliance Rate</span>
                        <span class="font-semibold text-gray-900">{{ $stats['proof_compliance_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-chart-area mr-2"></i>Monthly Trends ({{ date('Y') }})
            </h2>
            <a href="{{ route('remittance.reports.monthly') }}" class="text-blue-600 hover:text-blue-700 text-sm">
                View Detailed Report <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Count</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($monthlyTrends as $trend)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $trend['month'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($trend['count']) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($trend['total_amount'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($trend['avg_amount'], 2) }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                @php
                                    $maxAmount = collect($monthlyTrends)->max('total_amount');
                                    $percentage = $maxAmount > 0 ? ($trend['total_amount'] / $maxAmount) * 100 : 0;
                                @endphp
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Purpose Analysis & Transfer Methods -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Purpose Distribution -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-bullseye mr-2"></i>Purpose Distribution
                </h2>
                <a href="{{ route('remittance.reports.purpose') }}" class="text-blue-600 hover:text-blue-700 text-sm">
                    View Details <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="space-y-4">
                @foreach($purposeAnalysis as $purpose)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ $purpose['purpose_label'] }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $purpose['percentage'] }}%</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: {{ $purpose['percentage'] }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ number_format($purpose['total_amount'], 0) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $purpose['count'] }} transfers</p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Transfer Methods -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-exchange-alt mr-2"></i>Transfer Methods
            </h2>

            <div class="space-y-4">
                @foreach($transferMethods as $method)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ $method['method'] }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $method['percentage'] }}%</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: {{ $method['percentage'] }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ number_format($method['total_amount'], 0) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $method['count'] }} transfers</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Country Analysis & Top Candidates -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Country-wise Analysis -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-globe mr-2"></i>Top Countries by Remittance
            </h2>

            <div class="space-y-3">
                @foreach($countryAnalysis as $country)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $country['country'] }}</p>
                        <p class="text-xs text-gray-500">{{ $country['count'] }} transfers • Avg: {{ number_format($country['avg_amount'], 0) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">{{ number_format($country['total_amount'], 0) }}</p>
                        <p class="text-xs text-gray-500">PKR</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Remitting Candidates -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-star mr-2"></i>Top Remitting Candidates
            </h2>

            <div class="space-y-3">
                @foreach($topCandidates as $candidate)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $candidate->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $candidate->cnic }} • {{ $candidate->remittance_count }} transfers</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">{{ number_format($candidate->total_amount, 0) }}</p>
                        <p class="text-xs text-gray-500">PKR</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-link mr-2"></i>Quick Links to Detailed Reports
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('remittance.reports.monthly') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-calendar-alt text-blue-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">Monthly Report</p>
                    <p class="text-xs text-gray-600">Detailed monthly trends</p>
                </div>
            </a>
            <a href="{{ route('remittance.reports.purpose') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <i class="fas fa-bullseye text-green-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">Purpose Analysis</p>
                    <p class="text-xs text-gray-600">Usage breakdown</p>
                </div>
            </a>
            <a href="{{ route('remittance.reports.proof') }}" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                <i class="fas fa-file-invoice text-yellow-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">Proof Compliance</p>
                    <p class="text-xs text-gray-600">Receipt tracking</p>
                </div>
            </a>
            <a href="{{ route('remittance.reports.impact') }}" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                <i class="fas fa-chart-pie text-purple-600 text-2xl mr-3"></i>
                <div>
                    <p class="font-medium text-gray-900">Impact Analytics</p>
                    <p class="text-xs text-gray-600">Economic impact</p>
                </div>
            </a>
        </div>
    </div>

</div>
@endsection
