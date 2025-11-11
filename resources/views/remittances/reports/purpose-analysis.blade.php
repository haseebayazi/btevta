@extends('layouts.app')

@section('title', 'Purpose Analysis Report - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Purpose Analysis</h1>
            <p class="text-gray-600 mt-1">Detailed breakdown of remittance usage purposes</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittance.reports.export', ['type' => 'purpose', 'format' => 'excel']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </a>
            <a href="{{ route('remittance.reports.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    @php
        $totalAmount = collect($purposeAnalysis)->sum('total_amount');
        $totalCount = collect($purposeAnalysis)->sum('count');
        $topPurpose = collect($purposeAnalysis)->sortByDesc('total_amount')->first();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-medium">Total Categorized Amount</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalAmount, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">PKR</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-medium">Total Transfers</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalCount) }}</p>
            <p class="text-xs text-gray-500 mt-1">Categorized remittances</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm font-medium">Top Purpose</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $topPurpose['purpose_label'] ?? 'N/A' }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $topPurpose['percentage'] ?? 0 }}% of total</p>
        </div>
    </div>

    <!-- Purpose Breakdown -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-bullseye mr-2"></i>Purpose Breakdown
            </h2>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- List View -->
                <div class="space-y-4">
                    @foreach($purposeAnalysis as $index => $purpose)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $purpose['purpose_label'] }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $purpose['count'] }} transfers</p>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                {{ $purpose['percentage'] }}%
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Amount:</span>
                                <span class="font-semibold text-gray-900">PKR {{ number_format($purpose['total_amount'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Average Amount:</span>
                                <span class="font-medium text-gray-900">PKR {{ number_format($purpose['avg_amount'], 2) }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all" style="width: {{ $purpose['percentage'] }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Visual Chart (Pie Chart Simulation) -->
                <div>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 text-center">Distribution by Amount</h3>

                        <div class="space-y-3">
                            @php
                                $colors = [
                                    'bg-blue-500',
                                    'bg-green-500',
                                    'bg-yellow-500',
                                    'bg-purple-500',
                                    'bg-pink-500',
                                    'bg-indigo-500',
                                    'bg-red-500',
                                    'bg-orange-500',
                                    'bg-teal-500',
                                    'bg-cyan-500',
                                ];
                            @endphp

                            @foreach($purposeAnalysis as $index => $purpose)
                            <div class="flex items-center">
                                <div class="w-4 h-4 {{ $colors[$index % count($colors)] }} rounded-sm mr-3"></div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">{{ $purpose['purpose_label'] }}</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $purpose['percentage'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="{{ $colors[$index % count($colors)] }} h-2 rounded-full transition-all" style="width: {{ $purpose['percentage'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Purpose Rankings -->
                    <div class="mt-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-trophy mr-2 text-yellow-500"></i>Top 3 Purposes
                        </h3>
                        <div class="space-y-3">
                            @foreach(collect($purposeAnalysis)->sortByDesc('total_amount')->take(3) as $index => $purpose)
                            <div class="flex items-center bg-white rounded-lg p-3 shadow-sm">
                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg mr-3">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900">{{ $purpose['purpose_label'] }}</p>
                                    <p class="text-xs text-gray-600">PKR {{ number_format($purpose['total_amount'], 0) }} • {{ $purpose['count'] }} transfers</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-table mr-2"></i>Detailed Comparison Table
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount (PKR)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Amount (PKR)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% of Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach(collect($purposeAnalysis)->sortByDesc('total_amount')->values() as $index => $purpose)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index < 3 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600' }} font-bold text-sm">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $purpose['purpose_label'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            {{ number_format($purpose['count']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            {{ number_format($purpose['total_amount'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                            {{ number_format($purpose['avg_amount'], 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $purpose['percentage'] }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-6 py-4 font-bold text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ number_format($totalCount) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ number_format($totalAmount, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : '0' }}</td>
                        <td class="px-6 py-4 text-center font-bold text-gray-900">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Insights -->
    <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-green-900 mb-3">
            <i class="fas fa-lightbulb mr-2"></i>Key Insights
        </h3>
        <ul class="space-y-2 text-green-800">
            <li><i class="fas fa-check-circle mr-2"></i>Most common purpose: {{ $topPurpose['purpose_label'] ?? 'N/A' }} ({{ $topPurpose['count'] ?? 0 }} transfers)</li>
            <li><i class="fas fa-chart-line mr-2"></i>Highest value purpose: {{ collect($purposeAnalysis)->sortByDesc('total_amount')->first()['purpose_label'] ?? 'N/A' }}</li>
            <li><i class="fas fa-calculator mr-2"></i>Highest average transfer: {{ collect($purposeAnalysis)->sortByDesc('avg_amount')->first()['purpose_label'] ?? 'N/A' }} (PKR {{ number_format(collect($purposeAnalysis)->sortByDesc('avg_amount')->first()['avg_amount'] ?? 0, 0) }})</li>
            <li><i class="fas fa-info-circle mr-2"></i>Total unique purposes tracked: {{ count($purposeAnalysis) }}</li>
        </ul>
    </div>

</div>
@endsection
