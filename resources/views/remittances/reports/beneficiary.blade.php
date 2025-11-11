@extends('layouts.app')

@section('title', 'Beneficiary Analysis Report - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Beneficiary Analysis Report</h1>
            <p class="text-gray-600 mt-1">Analysis of remittance beneficiaries and recipients</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittance.reports.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-medium">Total Beneficiaries</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($beneficiaryReport['overview']['total']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Registered in system</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-medium">Active Beneficiaries</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($beneficiaryReport['overview']['active']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Currently receiving remittances</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm font-medium">Primary Beneficiaries</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($beneficiaryReport['overview']['primary']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Designated as primary recipients</p>
        </div>
    </div>

    <!-- Relationship Breakdown -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-users mr-2"></i>Beneficiary by Relationship
        </h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Bar Chart Representation -->
            <div class="space-y-4">
                @foreach($beneficiaryReport['by_relationship'] as $relationship)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 capitalize">{{ $relationship['relationship'] }}</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($relationship['count']) }}</span>
                            <span class="text-xs text-gray-500">({{ $relationship['percentage'] }}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all" style="width: {{ $relationship['percentage'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Top Relationships Cards -->
            <div class="space-y-4">
                <h3 class="font-semibold text-gray-900 mb-4">Top Relationships</h3>
                @foreach(collect($beneficiaryReport['by_relationship'])->sortByDesc('count')->take(5) as $index => $relationship)
                <div class="flex items-center p-4 bg-gradient-to-r {{ ['from-blue-50 to-blue-100', 'from-green-50 to-green-100', 'from-yellow-50 to-yellow-100', 'from-purple-50 to-purple-100', 'from-pink-50 to-pink-100'][$index % 5] }} rounded-lg">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mr-4 shadow-sm">
                        <span class="text-xl font-bold text-gray-700">{{ $index + 1 }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 capitalize">{{ $relationship['relationship'] }}</p>
                        <p class="text-sm text-gray-600">{{ number_format($relationship['count']) }} beneficiaries ({{ $relationship['percentage'] }}%)</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Banking Information Completeness -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-university mr-2"></i>Banking Information Completeness
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-credit-card text-blue-600 text-3xl"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($beneficiaryReport['banking_info']['with_account']) }}</p>
                <p class="text-sm text-gray-600 mt-1">With Bank Account</p>
                @php
                    $accountPercentage = $beneficiaryReport['overview']['total'] > 0
                        ? round(($beneficiaryReport['banking_info']['with_account'] / $beneficiaryReport['overview']['total']) * 100, 1)
                        : 0;
                @endphp
                <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $accountPercentage }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ $accountPercentage }}% coverage</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-landmark text-green-600 text-3xl"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($beneficiaryReport['banking_info']['with_iban']) }}</p>
                <p class="text-sm text-gray-600 mt-1">With IBAN</p>
                @php
                    $ibanPercentage = $beneficiaryReport['overview']['total'] > 0
                        ? round(($beneficiaryReport['banking_info']['with_iban'] / $beneficiaryReport['overview']['total']) * 100, 1)
                        : 0;
                @endphp
                <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $ibanPercentage }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ $ibanPercentage }}% coverage</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-6 text-center">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-mobile-alt text-purple-600 text-3xl"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($beneficiaryReport['banking_info']['with_mobile_wallet']) }}</p>
                <p class="text-sm text-gray-600 mt-1">With Mobile Wallet</p>
                @php
                    $walletPercentage = $beneficiaryReport['overview']['total'] > 0
                        ? round(($beneficiaryReport['banking_info']['with_mobile_wallet'] / $beneficiaryReport['overview']['total']) * 100, 1)
                        : 0;
                @endphp
                <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $walletPercentage }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ $walletPercentage }}% coverage</p>
            </div>
        </div>
    </div>

    <!-- Beneficiary Status Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Distribution -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-chart-pie mr-2"></i>Status Distribution
            </h2>

            @php
                $inactiveCount = $beneficiaryReport['overview']['total'] - $beneficiaryReport['overview']['active'];
                $activePercentage = $beneficiaryReport['overview']['total'] > 0
                    ? round(($beneficiaryReport['overview']['active'] / $beneficiaryReport['overview']['total']) * 100, 1)
                    : 0;
                $inactivePercentage = 100 - $activePercentage;
            @endphp

            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Active Beneficiaries</span>
                        <span class="text-sm font-semibold text-green-600">{{ $activePercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-green-500 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold" style="width: {{ $activePercentage }}%">
                            {{ number_format($beneficiaryReport['overview']['active']) }}
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Inactive Beneficiaries</span>
                        <span class="text-sm font-semibold text-red-600">{{ $inactivePercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-red-500 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold" style="width: {{ $inactivePercentage }}%">
                            {{ number_format($inactiveCount) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Beneficiary Status -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-star mr-2"></i>Primary Beneficiary Designation
            </h2>

            @php
                $nonPrimaryCount = $beneficiaryReport['overview']['total'] - $beneficiaryReport['overview']['primary'];
                $primaryPercentage = $beneficiaryReport['overview']['total'] > 0
                    ? round(($beneficiaryReport['overview']['primary'] / $beneficiaryReport['overview']['total']) * 100, 1)
                    : 0;
                $nonPrimaryPercentage = 100 - $primaryPercentage;
            @endphp

            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Primary Beneficiaries</span>
                        <span class="text-sm font-semibold text-purple-600">{{ $primaryPercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-purple-500 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold" style="width: {{ $primaryPercentage }}%">
                            {{ number_format($beneficiaryReport['overview']['primary']) }}
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Secondary Beneficiaries</span>
                        <span class="text-sm font-semibold text-gray-600">{{ $nonPrimaryPercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-gray-400 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold" style="width: {{ $nonPrimaryPercentage }}%">
                            {{ number_format($nonPrimaryCount) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights & Recommendations -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">
            <i class="fas fa-lightbulb mr-2"></i>Key Insights & Recommendations
        </h3>
        <ul class="space-y-2 text-blue-800">
            <li><i class="fas fa-users mr-2"></i>Total of {{ number_format($beneficiaryReport['overview']['total']) }} beneficiaries registered, with {{ number_format($beneficiaryReport['overview']['active']) }} currently active</li>
            @if(count($beneficiaryReport['by_relationship']) > 0)
            <li><i class="fas fa-heart mr-2"></i>Most common beneficiary relationship: {{ collect($beneficiaryReport['by_relationship'])->sortByDesc('count')->first()['relationship'] ?? 'N/A' }}</li>
            @endif
            <li><i class="fas fa-university mr-2"></i>Banking information completeness: {{ $accountPercentage }}% have account details, {{ $ibanPercentage }}% have IBAN</li>
            @if($walletPercentage > 50)
            <li><i class="fas fa-mobile-alt mr-2"></i>Good adoption of mobile wallets ({{ $walletPercentage }}%) for easier remittance transfers</li>
            @endif
            @if($accountPercentage < 80)
            <li><i class="fas fa-exclamation-circle mr-2"></i>Recommendation: Encourage {{ number_format($beneficiaryReport['overview']['total'] - $beneficiaryReport['banking_info']['with_account']) }} beneficiaries to complete banking information</li>
            @endif
        </ul>
    </div>

</div>
@endsection
