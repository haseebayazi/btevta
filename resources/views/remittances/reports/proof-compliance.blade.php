@extends('layouts.app')

@section('title', 'Proof Compliance Report - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Proof of Transfer Compliance Report</h1>
            <p class="text-gray-600 mt-1">Track receipt documentation and compliance rates</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittance.reports.export', ['type' => 'compliance', 'format' => 'excel']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </a>
            <a href="{{ route('remittances.index') }}?has_proof=0" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                View Missing Proofs
            </a>
            <a href="{{ route('remittance.reports.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Overall Compliance -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-medium">Total Remittances</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($complianceReport['overall']['total_remittances']) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-medium">With Proof</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($complianceReport['overall']['with_proof']) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <p class="text-gray-600 text-sm font-medium">Without Proof</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($complianceReport['overall']['without_proof']) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm font-medium">Compliance Rate</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ $complianceReport['overall']['compliance_rate'] }}%</p>
        </div>
    </div>

    <!-- Compliance Target -->
    @php
        $targetRate = 70; // 70% compliance target
        $currentRate = $complianceReport['overall']['compliance_rate'];
        $status = $currentRate >= $targetRate ? 'success' : 'warning';
    @endphp

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-target mr-2"></i>Compliance Target Progress
            </h2>
            <span class="text-sm font-medium text-gray-600">Target: {{ $targetRate }}%</span>
        </div>

        <div class="relative">
            <div class="w-full bg-gray-200 rounded-full h-8">
                <div class="bg-gradient-to-r {{ $status === 'success' ? 'from-green-500 to-green-600' : 'from-yellow-500 to-orange-500' }} h-8 rounded-full flex items-center justify-center transition-all" style="width: {{ min($currentRate, 100) }}%">
                    <span class="text-white font-bold text-sm">{{ $currentRate }}%</span>
                </div>
            </div>
            <div class="absolute top-0 left-0 w-full h-8 flex items-center" style="left: {{ $targetRate }}%">
                <div class="w-1 h-10 bg-gray-400 -mt-1"></div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-center">
            @if($status === 'success')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <i class="fas fa-check-circle text-green-600 text-3xl mb-2"></i>
                <p class="text-green-800 font-semibold">Target Achieved!</p>
                <p class="text-green-700 text-sm">Compliance rate exceeds {{ $targetRate }}% target</p>
            </div>
            @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl mb-2"></i>
                <p class="text-yellow-800 font-semibold">Action Required</p>
                <p class="text-yellow-700 text-sm">Need {{ number_format($complianceReport['overall']['without_proof']) }} more proofs to reach {{ $targetRate }}% target</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Compliance by Purpose -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-bullseye mr-2"></i>Compliance by Purpose
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">With Proof</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Without Proof</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visual</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($complianceReport['by_purpose'] as $purpose)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $purpose['purpose_label'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            {{ number_format($purpose['total']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 font-semibold">
                            {{ number_format($purpose['with_proof']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600 font-semibold">
                            {{ number_format($purpose['without_proof']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $purpose['compliance_rate'] >= $targetRate ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $purpose['compliance_rate'] }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-green-500 h-3 rounded-full transition-all" style="width: {{ $purpose['compliance_rate'] }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Monthly Compliance Trend -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-chart-line mr-2"></i>Monthly Compliance Trend ({{ date('Y') }})
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">With Proof</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Without Proof</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($complianceReport['by_month'] as $month)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $month['month'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            {{ number_format($month['total']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 font-semibold">
                            {{ number_format($month['with_proof']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600 font-semibold">
                            {{ number_format($month['without_proof']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $month['compliance_rate'] >= $targetRate ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $month['compliance_rate'] }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-green-500 h-3 rounded-full transition-all" style="width: {{ $month['compliance_rate'] }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Insights -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-yellow-900 mb-3">
            <i class="fas fa-lightbulb mr-2"></i>Compliance Insights
        </h3>
        <ul class="space-y-2 text-yellow-800">
            @php
                $bestPurpose = collect($complianceReport['by_purpose'])->sortByDesc('compliance_rate')->first();
                $worstPurpose = collect($complianceReport['by_purpose'])->sortBy('compliance_rate')->first();
                $bestMonth = collect($complianceReport['by_month'])->sortByDesc('compliance_rate')->first();
            @endphp
            <li><i class="fas fa-star mr-2"></i>Best performing purpose: {{ $bestPurpose['purpose_label'] ?? 'N/A' }} ({{ $bestPurpose['compliance_rate'] ?? 0 }}% compliance)</li>
            <li><i class="fas fa-exclamation-circle mr-2"></i>Needs improvement: {{ $worstPurpose['purpose_label'] ?? 'N/A' }} ({{ $worstPurpose['compliance_rate'] ?? 0 }}% compliance)</li>
            <li><i class="fas fa-calendar-check mr-2"></i>Best compliance month: {{ $bestMonth['month'] ?? 'N/A' }} ({{ $bestMonth['compliance_rate'] ?? 0 }}%)</li>
            <li><i class="fas fa-clipboard-list mr-2"></i>Total missing proofs: {{ $complianceReport['overall']['without_proof'] }} remittances require documentation</li>
        </ul>
    </div>

</div>
@endsection
