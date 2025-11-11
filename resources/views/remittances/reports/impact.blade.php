@extends('layouts.app')

@section('title', 'Remittance Impact Analytics - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Impact Analytics</h1>
            <p class="text-gray-600 mt-1">Economic and social impact of worker remittances</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittance.reports.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Economic Impact -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-sm p-8 border-l-4 border-blue-500">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
            <i class="fas fa-coins mr-3 text-blue-600"></i>Economic Impact
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <p class="text-gray-600 text-sm font-medium mb-2">Total Economic Inflow</p>
                <p class="text-4xl font-bold text-blue-600">{{ number_format($impactData['economic_impact']['total_inflow'], 0) }}</p>
                <p class="text-sm text-gray-500 mt-1">PKR</p>
            </div>

            <div class="bg-white rounded-lg p-6 shadow-sm">
                <p class="text-gray-600 text-sm font-medium mb-2">Families Benefited</p>
                <p class="text-4xl font-bold text-green-600">{{ number_format($impactData['economic_impact']['total_families_benefited']) }}</p>
                <p class="text-sm text-gray-500 mt-1">Unique candidates</p>
            </div>

            <div class="bg-white rounded-lg p-6 shadow-sm">
                <p class="text-gray-600 text-sm font-medium mb-2">Average per Family</p>
                <p class="text-4xl font-bold text-purple-600">{{ number_format($impactData['economic_impact']['avg_per_family'], 0) }}</p>
                <p class="text-sm text-gray-500 mt-1">PKR</p>
            </div>
        </div>
    </div>

    <!-- Behavioral Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Time to First Remittance -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-clock mr-2"></i>Time to First Remittance
            </h2>

            <div class="text-center mb-6">
                @if($impactData['avg_time_to_first_remittance'])
                <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full mb-4">
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-600">{{ number_format($impactData['avg_time_to_first_remittance'], 1) }}</p>
                        <p class="text-sm font-medium text-blue-800">Days</p>
                    </div>
                </div>
                <p class="text-gray-600">Average time from deployment to first remittance</p>
                @else
                <div class="text-gray-500 py-12">
                    <i class="fas fa-info-circle text-4xl mb-3"></i>
                    <p>Insufficient data to calculate</p>
                </div>
                @endif
            </div>

            @if($impactData['avg_time_to_first_remittance'])
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Analysis</h3>
                <ul class="space-y-1 text-sm text-gray-700">
                    @if($impactData['avg_time_to_first_remittance'] <= 30)
                    <li><i class="fas fa-check-circle text-green-500 mr-2"></i>Excellent - Workers send remittances within first month</li>
                    @elseif($impactData['avg_time_to_first_remittance'] <= 60)
                    <li><i class="fas fa-check-circle text-blue-500 mr-2"></i>Good - Remittances begin within 2 months of deployment</li>
                    @else
                    <li><i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>Improvement needed - Longer delay before first remittance</li>
                    @endif
                </ul>
            </div>
            @endif
        </div>

        <!-- Remittance Frequency -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-chart-line mr-2"></i>Remittance Frequency
            </h2>

            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-green-100 to-green-200 rounded-full mb-4">
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-600">{{ number_format($impactData['remittance_frequency'], 1) }}</p>
                        <p class="text-sm font-medium text-green-800">per year</p>
                    </div>
                </div>
                <p class="text-gray-600">Average remittances per candidate per year</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Frequency Analysis</h3>
                <ul class="space-y-1 text-sm text-gray-700">
                    @if($impactData['remittance_frequency'] >= 12)
                    <li><i class="fas fa-star text-yellow-500 mr-2"></i>Excellent - Monthly remittance pattern</li>
                    @elseif($impactData['remittance_frequency'] >= 6)
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Good - Bi-monthly remittance pattern</li>
                    @elseif($impactData['remittance_frequency'] >= 4)
                    <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>Fair - Quarterly remittance pattern</li>
                    @else
                    <li><i class="fas fa-exclamation-circle text-orange-500 mr-2"></i>Low frequency - Room for improvement</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- Purpose Impact Breakdown -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-bullseye mr-2"></i>Social Impact by Purpose
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($impactData['purpose_breakdown'] as $purpose)
            <div class="border border-gray-200 rounded-lg p-5 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-semibold text-gray-900">{{ $purpose['purpose_label'] }}</h3>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                        {{ $purpose['percentage'] }}%
                    </span>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Transfers:</span>
                        <span class="font-semibold text-gray-900">{{ number_format($purpose['count']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Impact:</span>
                        <span class="font-semibold text-gray-900">PKR {{ number_format($purpose['total_amount'], 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Per Transfer:</span>
                        <span class="font-medium text-gray-900">PKR {{ number_format($purpose['avg_amount'], 0) }}</span>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $purpose['percentage'] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Impact Indicators -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Financial Inclusion -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-university mr-2"></i>Financial Inclusion Impact
            </h2>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">Bank Account Usage</p>
                        <p class="text-sm text-gray-600">Remittances through formal channels</p>
                    </div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ number_format($impactData['economic_impact']['total_families_benefited']) }}
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">Digital Transactions</p>
                        <p class="text-sm text-gray-600">Families using modern banking</p>
                    </div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ number_format($impactData['economic_impact']['total_families_benefited']) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Livelihood Support -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-home mr-2"></i>Livelihood Support Impact
            </h2>

            <div class="space-y-4">
                @php
                    $familySupportPurpose = collect($impactData['purpose_breakdown'])->firstWhere('purpose', 'family_support');
                    $educationPurpose = collect($impactData['purpose_breakdown'])->firstWhere('purpose', 'education');
                    $healthPurpose = collect($impactData['purpose_breakdown'])->firstWhere('purpose', 'health');
                @endphp

                <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">Family Support</p>
                        <p class="text-sm text-gray-600">Basic household needs</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-purple-600">PKR {{ number_format($familySupportPurpose['total_amount'] ?? 0, 0) }}</p>
                        <p class="text-xs text-gray-600">{{ $familySupportPurpose['count'] ?? 0 }} transfers</p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">Education Investment</p>
                        <p class="text-sm text-gray-600">School fees & education</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-indigo-600">PKR {{ number_format($educationPurpose['total_amount'] ?? 0, 0) }}</p>
                        <p class="text-xs text-gray-600">{{ $educationPurpose['count'] ?? 0 }} transfers</p>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 bg-pink-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">Healthcare Access</p>
                        <p class="text-sm text-gray-600">Medical & health expenses</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-pink-600">PKR {{ number_format($healthPurpose['total_amount'] ?? 0, 0) }}</p>
                        <p class="text-xs text-gray-600">{{ $healthPurpose['count'] ?? 0 }} transfers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-l-4 border-purple-400 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-purple-900 mb-3">
            <i class="fas fa-chart-pie mr-2"></i>Impact Summary
        </h3>
        <ul class="space-y-2 text-purple-800">
            <li><i class="fas fa-globe mr-2"></i>{{ number_format($impactData['economic_impact']['total_families_benefited']) }} families receiving regular financial support from deployed workers</li>
            <li><i class="fas fa-coins mr-2"></i>Total PKR {{ number_format($impactData['economic_impact']['total_inflow'], 0) }} contributed to local economy</li>
            <li><i class="fas fa-users mr-2"></i>Average family receiving PKR {{ number_format($impactData['economic_impact']['avg_per_family'], 0) }} in remittances</li>
            @if($impactData['avg_time_to_first_remittance'])
            <li><i class="fas fa-clock mr-2"></i>Workers typically begin remitting within {{ number_format($impactData['avg_time_to_first_remittance'], 0) }} days of deployment</li>
            @endif
            <li><i class="fas fa-chart-line mr-2"></i>Remittance frequency: {{ number_format($impactData['remittance_frequency'], 1) }} transfers per candidate annually</li>
        </ul>
    </div>

</div>
@endsection
