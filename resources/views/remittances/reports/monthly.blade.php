@extends('layouts.app')

@section('title', 'Monthly Remittance Report - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Monthly Remittance Report</h1>
            <p class="text-gray-600 mt-1">Detailed monthly trends and analysis for {{ $year }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittance.reports.export', ['type' => 'monthly', 'format' => 'excel', 'year' => $year]) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </a>
            <a href="{{ route('remittance.reports.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Year Selector -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="GET" action="{{ route('remittance.reports.monthly') }}" class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700">Select Year:</label>
            <select name="year" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2">
                @foreach($years as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $yearlyTotal = collect($monthlyTrends)->sum('total_amount');
            $yearlyCount = collect($monthlyTrends)->sum('count');
            $yearlyAvg = $yearlyCount > 0 ? $yearlyTotal / $yearlyCount : 0;
            $peakMonth = collect($monthlyTrends)->sortByDesc('total_amount')->first();
        @endphp

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-medium">Yearly Total</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($yearlyTotal, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">PKR</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-medium">Total Transfers</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($yearlyCount) }}</p>
            <p class="text-xs text-gray-500 mt-1">In {{ $year }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <p class="text-gray-600 text-sm font-medium">Average Transfer</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($yearlyAvg, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">PKR</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm font-medium">Peak Month</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $peakMonth['month'] ?? 'N/A' }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($peakMonth['total_amount'] ?? 0, 0) }} PKR</p>
        </div>
    </div>

    <!-- Monthly Trends Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-table mr-2"></i>Monthly Breakdown
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount (PKR)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Amount (PKR)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% of Year Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visual</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($monthlyTrends as $trend)
                    @php
                        $percentOfTotal = $yearlyTotal > 0 ? ($trend['total_amount'] / $yearlyTotal) * 100 : 0;
                        $maxAmount = collect($monthlyTrends)->max('total_amount');
                        $barWidth = $maxAmount > 0 ? ($trend['total_amount'] / $maxAmount) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $trend['count'] == 0 ? 'opacity-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-sm font-bold text-blue-600">{{ $trend['month_number'] }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $trend['month'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            <span class="font-semibold">{{ number_format($trend['count']) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            <span class="font-semibold">{{ number_format($trend['total_amount'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                            <span class="font-medium">{{ number_format($trend['avg_amount'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ number_format($percentOfTotal, 2) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all" style="width: {{ $barWidth }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ number_format($yearlyCount) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ number_format($yearlyTotal, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">{{ number_format($yearlyAvg, 2) }}</td>
                        <td class="px-6 py-4 text-center font-bold text-gray-900">100%</td>
                        <td class="px-6 py-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Quarterly Analysis -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-chart-bar mr-2"></i>Quarterly Analysis
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @php
                $quarters = [
                    'Q1' => [1, 2, 3],
                    'Q2' => [4, 5, 6],
                    'Q3' => [7, 8, 9],
                    'Q4' => [10, 11, 12],
                ];
            @endphp

            @foreach($quarters as $quarterName => $months)
                @php
                    $quarterData = collect($monthlyTrends)->filter(function($trend) use ($months) {
                        return in_array($trend['month_number'], $months);
                    });
                    $qCount = $quarterData->sum('count');
                    $qTotal = $quarterData->sum('total_amount');
                    $qAvg = $qCount > 0 ? $qTotal / $qCount : 0;
                @endphp

                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">{{ $quarterName }}</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Transfers:</span>
                            <span class="text-sm font-medium text-gray-900">{{ number_format($qCount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total:</span>
                            <span class="text-sm font-medium text-gray-900">{{ number_format($qTotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Average:</span>
                            <span class="text-sm font-medium text-gray-900">{{ number_format($qAvg, 0) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Insights -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">
            <i class="fas fa-lightbulb mr-2"></i>Key Insights
        </h3>
        <ul class="space-y-2 text-blue-800">
            @php
                $activeMonths = collect($monthlyTrends)->filter(fn($t) => $t['count'] > 0)->count();
                $inactiveMonths = 12 - $activeMonths;
                $avgPerActiveMonth = $activeMonths > 0 ? $yearlyTotal / $activeMonths : 0;
            @endphp
            <li><i class="fas fa-check-circle mr-2"></i>Active months: {{ $activeMonths }} out of 12</li>
            @if($inactiveMonths > 0)
            <li><i class="fas fa-info-circle mr-2"></i>{{ $inactiveMonths }} month(s) with no remittances recorded</li>
            @endif
            <li><i class="fas fa-calculator mr-2"></i>Average per active month: PKR {{ number_format($avgPerActiveMonth, 0) }}</li>
            <li><i class="fas fa-star mr-2"></i>Peak performance in {{ $peakMonth['month'] ?? 'N/A' }} with {{ $peakMonth['count'] ?? 0 }} transfers</li>
        </ul>
    </div>

</div>
@endsection
