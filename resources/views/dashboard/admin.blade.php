@extends('layouts.app')

@section('title', 'Dashboard - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    {{-- ===== HEADER BANNER ===== --}}
    <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ config('app.full_name', 'WASL') }}</h1>
                <p class="text-blue-100 mt-0.5 text-sm">{{ config('app.tagline', 'Workforce Abroad Skills & Linkages') }}</p>
            </div>
            <div class="text-right hidden md:flex flex-col items-end gap-1">
                <span class="text-lg font-semibold text-white">{{ now()->format('l, d M Y') }}</span>
                <span class="text-sm text-blue-200">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }} &mdash; {{ auth()->user()->name }}</span>
                <a href="{{ route('dashboard.compliance-monitoring') }}" class="mt-1 inline-flex items-center gap-1 bg-white/20 hover:bg-white/30 text-white text-xs font-medium px-3 py-1.5 rounded-full transition">
                    <i class="fas fa-shield-alt"></i> Compliance Monitor
                </a>
            </div>
        </div>
    </div>

    {{-- ===== ALERTS ===== --}}
    @if(count($alerts) > 0)
    <div class="space-y-2">
        @foreach($alerts as $alert)
        @php
            $colors = [
                'danger'  => ['bg' => 'red',    'icon' => 'exclamation-circle'],
                'warning' => ['bg' => 'yellow', 'icon' => 'exclamation-triangle'],
                'info'    => ['bg' => 'blue',   'icon' => 'info-circle'],
            ];
            $c = $colors[$alert['type']] ?? $colors['info'];
        @endphp
        <div class="flex items-center justify-between bg-{{ $c['bg'] }}-50 border border-{{ $c['bg'] }}-200 rounded-lg px-4 py-3">
            <div class="flex items-center gap-2">
                <i class="fas fa-{{ $c['icon'] }} text-{{ $c['bg'] }}-600"></i>
                <span class="text-sm text-{{ $c['bg'] }}-800 font-medium">{{ $alert['message'] }}</span>
            </div>
            <a href="{{ $alert['action_url'] }}" class="text-{{ $c['bg'] }}-700 hover:text-{{ $c['bg'] }}-900 text-xs font-semibold whitespace-nowrap ml-4">
                View &rarr;
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== KPI CARDS (6) ===== --}}
    @php
        $thisMonthReg  = $stats['this_month_registrations'];
        $lastMonthReg  = $stats['last_month_registrations'];
        $thisMonthDep  = $stats['this_month_departures'];
        $lastMonthDep  = $stats['last_month_departures'];
        $regChange     = $lastMonthReg  > 0 ? round(($thisMonthReg  - $lastMonthReg)  / $lastMonthReg  * 100) : 0;
        $depChange     = $lastMonthDep  > 0 ? round(($thisMonthDep  - $lastMonthDep)  / $lastMonthDep  * 100) : 0;
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- Total --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_candidates']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">All candidates</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-2.5"><i class="fas fa-users text-blue-600 text-lg"></i></div>
            </div>
        </div>

        {{-- Active Pipeline --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Active</p>
                    <p class="text-3xl font-bold text-indigo-700 mt-1">{{ number_format($stats['active_pipeline']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">In pipeline</p>
                </div>
                <div class="bg-indigo-100 rounded-lg p-2.5"><i class="fas fa-stream text-indigo-600 text-lg"></i></div>
            </div>
        </div>

        {{-- In Training --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Training</p>
                    <p class="text-3xl font-bold text-green-700 mt-1">{{ number_format($stats['in_training']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['active_batches'] }} active batches</p>
                </div>
                <div class="bg-green-100 rounded-lg p-2.5"><i class="fas fa-graduation-cap text-green-600 text-lg"></i></div>
            </div>
        </div>

        {{-- Visa --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Visa</p>
                    <p class="text-3xl font-bold text-yellow-700 mt-1">{{ number_format($stats['pending_visas']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Processing + approved</p>
                </div>
                <div class="bg-yellow-100 rounded-lg p-2.5"><i class="fas fa-passport text-yellow-600 text-lg"></i></div>
            </div>
        </div>

        {{-- Departed --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Departed</p>
                    <p class="text-3xl font-bold text-purple-700 mt-1">{{ number_format($stats['departed'] + $stats['post_departure']) }}</p>
                    @if($depChange != 0)
                    <p class="text-xs mt-1 {{ $depChange > 0 ? 'text-green-600' : 'text-red-500' }}">
                        <i class="fas fa-arrow-{{ $depChange > 0 ? 'up' : 'down' }}"></i>
                        {{ abs($depChange) }}% vs last month
                    </p>
                    @else
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['week_departures'] }} this week</p>
                    @endif
                </div>
                <div class="bg-purple-100 rounded-lg p-2.5"><i class="fas fa-plane-departure text-purple-600 text-lg"></i></div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Completed</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-1">{{ number_format($stats['completed']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['completion_rate'] }}% success rate</p>
                </div>
                <div class="bg-emerald-100 rounded-lg p-2.5"><i class="fas fa-check-circle text-emerald-600 text-lg"></i></div>
            </div>
        </div>

    </div>

    {{-- ===== FULL PIPELINE VISUALIZATION ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-bold text-gray-900">Candidate Pipeline &mdash; All Stages</h3>
            <a href="{{ route('pipeline.index') }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                Full Pipeline View &rarr;
            </a>
        </div>

        @php
            $pipelineStages = [
                ['key' => 'listed',               'label' => 'Listed',           'color' => 'bg-slate-100 text-slate-700 border-slate-300'],
                ['key' => 'pre_departure_docs',    'label' => 'Pre-Dep Docs',     'color' => 'bg-sky-100 text-sky-700 border-sky-300'],
                ['key' => 'screening',             'label' => 'Screening',        'color' => 'bg-amber-100 text-amber-700 border-amber-300'],
                ['key' => 'screened',              'label' => 'Screened',         'color' => 'bg-blue-100 text-blue-700 border-blue-300'],
                ['key' => 'registered',            'label' => 'Registered',       'color' => 'bg-cyan-100 text-cyan-700 border-cyan-300'],
                ['key' => 'in_training',           'label' => 'Training',         'color' => 'bg-green-100 text-green-700 border-green-300'],
                ['key' => 'training_completed',    'label' => 'Tr. Done',         'color' => 'bg-teal-100 text-teal-700 border-teal-300'],
                ['key' => 'visa_processing',       'label' => 'Visa Proc.',       'color' => 'bg-yellow-100 text-yellow-700 border-yellow-300'],
                ['key' => 'visa_approved',         'label' => 'Visa Approved',    'color' => 'bg-lime-100 text-lime-700 border-lime-300'],
                ['key' => 'departure_processing',  'label' => 'Dep. Processing',  'color' => 'bg-orange-100 text-orange-700 border-orange-300'],
                ['key' => 'ready_to_depart',       'label' => 'Ready',            'color' => 'bg-indigo-100 text-indigo-700 border-indigo-300'],
                ['key' => 'departed',              'label' => 'Departed',         'color' => 'bg-purple-100 text-purple-700 border-purple-300'],
                ['key' => 'post_departure',        'label' => 'Post-Dep',         'color' => 'bg-violet-100 text-violet-700 border-violet-300'],
                ['key' => 'completed',             'label' => 'Completed',        'color' => 'bg-emerald-100 text-emerald-700 border-emerald-300'],
            ];
            $total = max(1, $stats['total_candidates']);
        @endphp

        {{-- Active pipeline flow --}}
        <div class="flex flex-wrap gap-2 items-center">
            @foreach($pipelineStages as $i => $stage)
            <div class="flex items-center gap-1.5">
                <a href="{{ route('candidates.index', ['status' => $stage['key']]) }}"
                   class="flex flex-col items-center border {{ $stage['color'] }} rounded-lg px-3 py-2 min-w-[72px] hover:shadow-md transition group">
                    <span class="text-xl font-bold group-hover:scale-105 transition">{{ number_format($stats[$stage['key']] ?? 0) }}</span>
                    <span class="text-xs font-medium mt-0.5 text-center leading-tight">{{ $stage['label'] }}</span>
                    <span class="text-xs opacity-60 mt-0.5">{{ $total > 0 ? round(($stats[$stage['key']] ?? 0) / $total * 100, 1) : 0 }}%</span>
                </a>
                @if($i < count($pipelineStages) - 1)
                <i class="fas fa-chevron-right text-gray-300 text-xs flex-shrink-0"></i>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Terminal states --}}
        <div class="flex flex-wrap gap-3 mt-4 pt-4 border-t border-gray-100">
            <span class="text-xs text-gray-400 font-medium self-center">Terminal:</span>
            <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-600 border border-gray-300 rounded px-3 py-1.5 text-xs font-medium">
                <i class="fas fa-pause-circle"></i> Deferred: {{ number_format($stats['deferred']) }}
            </span>
            <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-600 border border-red-200 rounded px-3 py-1.5 text-xs font-medium">
                <i class="fas fa-times-circle"></i> Rejected: {{ number_format($stats['rejected']) }}
            </span>
            <span class="inline-flex items-center gap-1.5 bg-gray-50 text-gray-500 border border-gray-200 rounded px-3 py-1.5 text-xs font-medium">
                <i class="fas fa-sign-out-alt"></i> Withdrawn: {{ number_format($stats['withdrawn']) }}
            </span>
        </div>
    </div>

    {{-- ===== CHARTS ROW ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Monthly Trend (12 months real data) --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900">12-Month Trend</h3>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span><span class="inline-block w-3 h-1 bg-blue-500 rounded mr-1"></span>Registrations</span>
                    <span><span class="inline-block w-3 h-1 bg-emerald-500 rounded mr-1"></span>Departures</span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        {{-- Quick Operational Stats --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4">Operations Snapshot</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-layer-group text-blue-600 text-sm"></i>
                        </div>
                        <span class="text-sm text-gray-700">Active Batches</span>
                    </div>
                    <a href="{{ route('batches.index') }}" class="font-bold text-gray-900 hover:text-blue-600">{{ number_format($stats['active_batches']) }}</a>
                </div>

                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg hover:bg-red-100 transition">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                        </div>
                        <span class="text-sm text-gray-700">Pending Complaints</span>
                    </div>
                    <a href="{{ route('complaints.index') }}" class="font-bold text-gray-900 hover:text-red-600">{{ number_format($stats['pending_complaints']) }}</a>
                </div>

                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-envelope text-yellow-600 text-sm"></i>
                        </div>
                        <span class="text-sm text-gray-700">Pending Reply</span>
                    </div>
                    <a href="{{ route('correspondence.index') }}" class="font-bold text-gray-900 hover:text-yellow-600">{{ number_format($stats['pending_correspondence']) }}</a>
                </div>

                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-amber-600 text-sm"></i>
                        </div>
                        <span class="text-sm text-gray-700">Pending Visas</span>
                    </div>
                    <a href="{{ route('visa-processing.index') }}" class="font-bold text-gray-900 hover:text-amber-600">{{ number_format($stats['pending_visas']) }}</a>
                </div>

                <div class="flex items-center justify-between p-3 bg-rose-50 rounded-lg hover:bg-rose-100 transition">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-rose-600 text-sm"></i>
                        </div>
                        <span class="text-sm text-gray-700">Missing Proof</span>
                    </div>
                    <a href="{{ route('remittance.alerts.index', ['type' => 'missing_proof']) }}" class="font-bold text-gray-900 hover:text-rose-600">{{ number_format($stats['remittances_missing_proof']) }}</a>
                </div>

                <div class="pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Today's Registrations</span>
                        <span class="font-bold text-gray-900">
                            {{ $stats['today_registrations'] }}
                            @if($stats['registration_trend'] != 0)
                            <span class="text-xs {{ $stats['registration_trend'] > 0 ? 'text-green-500' : 'text-red-500' }}">
                                ({{ $stats['registration_trend'] > 0 ? '+' : '' }}{{ $stats['registration_trend'] }}%)
                            </span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-2">
                        <span class="text-gray-600">Departures This Week</span>
                        <span class="font-bold text-gray-900">{{ $stats['week_departures'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== CAMPUS & TRADE CHARTS ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Campus Performance --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900">Campus Breakdown</h3>
                <a href="{{ route('reports.campus-performance') }}" class="text-blue-600 text-xs font-medium hover:text-blue-800">Full Report &rarr;</a>
            </div>
            @if(!empty($stats['campus_names']))
            <div class="h-56">
                <canvas id="campusChart"></canvas>
            </div>
            @else
            <div class="flex items-center justify-center h-56 text-gray-400 text-sm">
                <div class="text-center"><i class="fas fa-building text-3xl mb-2 block"></i>No campus data yet</div>
            </div>
            @endif
        </div>

        {{-- Trade Distribution --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900">Trade Distribution</h3>
                <a href="{{ route('reports.index') }}" class="text-blue-600 text-xs font-medium hover:text-blue-800">View Reports &rarr;</a>
            </div>
            @if(!empty($stats['trade_names']))
            <div class="h-56">
                <canvas id="tradeChart"></canvas>
            </div>
            @else
            <div class="flex items-center justify-center h-56 text-gray-400 text-sm">
                <div class="text-center"><i class="fas fa-tools text-3xl mb-2 block"></i>No trade data yet</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== PERFORMANCE METRICS + REMITTANCE ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Performance Metrics --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4">Performance Metrics</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="pb-2 text-left font-semibold text-gray-600 text-xs uppercase">Metric</th>
                        <th class="pb-2 text-right font-semibold text-gray-600 text-xs uppercase">This Month</th>
                        <th class="pb-2 text-right font-semibold text-gray-600 text-xs uppercase">Last Month</th>
                        <th class="pb-2 text-right font-semibold text-gray-600 text-xs uppercase">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $regDelta = $stats['last_month_registrations'] > 0
                            ? round(($stats['this_month_registrations'] - $stats['last_month_registrations']) / $stats['last_month_registrations'] * 100, 1) : 0;
                        $depDelta = $stats['last_month_departures'] > 0
                            ? round(($stats['this_month_departures'] - $stats['last_month_departures']) / $stats['last_month_departures'] * 100, 1) : 0;
                        $procDelta = $stats['avg_processing_days'] - $stats['last_month_avg_processing'];
                        $resDelta  = $stats['complaint_resolution_rate'] - $stats['last_month_resolution_rate'];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-medium text-gray-800">Registrations</td>
                        <td class="py-3 text-right font-bold">{{ $stats['this_month_registrations'] }}</td>
                        <td class="py-3 text-right text-gray-400">{{ $stats['last_month_registrations'] }}</td>
                        <td class="py-3 text-right">
                            <span class="text-xs font-semibold {{ $regDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $regDelta >= 0 ? '+' : '' }}{{ $regDelta }}%
                            </span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-medium text-gray-800">Departures</td>
                        <td class="py-3 text-right font-bold">{{ $stats['this_month_departures'] }}</td>
                        <td class="py-3 text-right text-gray-400">{{ $stats['last_month_departures'] }}</td>
                        <td class="py-3 text-right">
                            <span class="text-xs font-semibold {{ $depDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $depDelta >= 0 ? '+' : '' }}{{ $depDelta }}%
                            </span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-medium text-gray-800">Avg. Processing (days)</td>
                        <td class="py-3 text-right font-bold">{{ $stats['avg_processing_days'] ?: '—' }}</td>
                        <td class="py-3 text-right text-gray-400">{{ $stats['last_month_avg_processing'] ?: '—' }}</td>
                        <td class="py-3 text-right">
                            @if($stats['avg_processing_days'] && $stats['last_month_avg_processing'])
                            <span class="text-xs font-semibold {{ $procDelta <= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $procDelta > 0 ? '+' : '' }}{{ $procDelta }}d
                            </span>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-medium text-gray-800">Complaint Resolution</td>
                        <td class="py-3 text-right font-bold">{{ $stats['complaint_resolution_rate'] }}%</td>
                        <td class="py-3 text-right text-gray-400">{{ $stats['last_month_resolution_rate'] }}%</td>
                        <td class="py-3 text-right">
                            <span class="text-xs font-semibold {{ $resDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $resDelta >= 0 ? '+' : '' }}{{ $resDelta }}%
                            </span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 font-medium text-gray-800">Completion Rate</td>
                        <td class="py-3 text-right font-bold" colspan="2">{{ $stats['completion_rate'] }}%</td>
                        <td class="py-3 text-right">
                            <div class="w-20 bg-gray-200 rounded-full h-1.5 ml-auto">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width:{{ min(100, $stats['completion_rate']) }}%"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Remittance Overview --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900">Remittance Overview</h3>
                <a href="{{ route('remittances.index') }}" class="text-blue-600 text-xs font-medium hover:text-blue-800">View All &rarr;</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <p class="text-xs text-emerald-600 font-medium uppercase tracking-wide">Total Records</p>
                    <p class="text-2xl font-bold text-emerald-900 mt-1">{{ number_format($stats['remittances_total']) }}</p>
                    <p class="text-xs text-emerald-700 mt-1">PKR {{ number_format($stats['remittances_amount'], 0) }}</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">This Month</p>
                    <p class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($stats['remittances_this_month_count']) }}</p>
                    <p class="text-xs text-blue-700 mt-1">PKR {{ number_format($stats['remittances_this_month_amount'], 0) }}</p>
                </div>
                <a href="{{ route('remittances.index', ['status' => 'pending']) }}" class="bg-amber-50 border border-amber-200 rounded-lg p-4 hover:bg-amber-100 transition block">
                    <p class="text-xs text-amber-600 font-medium uppercase tracking-wide">Pending Verify</p>
                    <p class="text-2xl font-bold text-amber-900 mt-1">{{ number_format($stats['remittances_pending']) }}</p>
                    <p class="text-xs text-amber-600 mt-1">Needs review</p>
                </a>
                <a href="{{ route('remittance.alerts.index', ['type' => 'missing_proof']) }}" class="bg-rose-50 border border-rose-200 rounded-lg p-4 hover:bg-rose-100 transition block">
                    <p class="text-xs text-rose-600 font-medium uppercase tracking-wide">Missing Proof</p>
                    <p class="text-2xl font-bold text-rose-900 mt-1">{{ number_format($stats['remittances_missing_proof']) }}</p>
                    <p class="text-xs text-rose-600 mt-1">Action required</p>
                </a>
            </div>
        </div>
    </div>

    {{-- ===== CAMPUS TABLE ===== --}}
    @if(!empty($roleData['campuses']) && $roleData['campuses']->count())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-900">Campus Overview</h3>
            <a href="{{ route('reports.campus-performance') }}" class="text-blue-600 text-xs font-medium hover:text-blue-800">Full Report &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 rounded-lg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Campus</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Candidates</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase hidden md:table-cell">Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($roleData['campuses']->take(8) as $campus)
                    @php $share = $stats['total_candidates'] > 0 ? round($campus->candidates_count / $stats['total_candidates'] * 100, 1) : 0; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <a href="{{ route('campuses.show', $campus->id) }}" class="hover:text-blue-600">{{ $campus->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($campus->candidates_count) }}</td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $share }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-10 text-right">{{ $share }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== RECENT ACTIVITIES ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-900">Recent Activity</h3>
            <a href="{{ route('admin.audit-logs') }}" class="text-blue-600 text-xs font-medium hover:text-blue-800">View All &rarr;</a>
        </div>
        <div class="space-y-2">
            @forelse($recentActivities as $activity)
            <div class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg transition">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-blue-600 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">
                        <span class="font-medium">{{ $activity->user_name }}</span>
                        <span class="text-gray-500"> {{ $activity->action }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-400 text-sm py-8">No recent activity</p>
            @endforelse
        </div>
    </div>

    {{-- ===== QUICK ACTIONS ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <a href="{{ route('import.candidates.form') }}" class="bg-white hover:bg-blue-50 border-2 border-blue-100 hover:border-blue-300 rounded-xl p-5 text-center transition group">
            <i class="fas fa-file-import text-blue-600 text-2xl mb-2 block group-hover:scale-110 transition"></i>
            <p class="font-semibold text-gray-800 text-sm">Import Candidates</p>
            <p class="text-xs text-gray-500 mt-0.5">Bulk via Excel</p>
        </a>
        <a href="{{ route('candidates.create') }}" class="bg-white hover:bg-green-50 border-2 border-green-100 hover:border-green-300 rounded-xl p-5 text-center transition group">
            <i class="fas fa-user-plus text-green-600 text-2xl mb-2 block group-hover:scale-110 transition"></i>
            <p class="font-semibold text-gray-800 text-sm">Add Candidate</p>
            <p class="text-xs text-gray-500 mt-0.5">Register new</p>
        </a>
        <a href="{{ route('reports.index') }}" class="bg-white hover:bg-purple-50 border-2 border-purple-100 hover:border-purple-300 rounded-xl p-5 text-center transition group">
            <i class="fas fa-chart-bar text-purple-600 text-2xl mb-2 block group-hover:scale-110 transition"></i>
            <p class="font-semibold text-gray-800 text-sm">Reports</p>
            <p class="text-xs text-gray-500 mt-0.5">Analytics & exports</p>
        </a>
        <a href="{{ route('dashboard.compliance-monitoring') }}" class="bg-white hover:bg-indigo-50 border-2 border-indigo-100 hover:border-indigo-300 rounded-xl p-5 text-center transition group">
            <i class="fas fa-shield-alt text-indigo-600 text-2xl mb-2 block group-hover:scale-110 transition"></i>
            <p class="font-semibold text-gray-800 text-sm">Compliance</p>
            <p class="text-xs text-gray-500 mt-0.5">Monitor SLA & docs</p>
        </a>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Monthly Trend Chart (12 months, real data) ──
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($stats['trend_labels']) !!},
                datasets: [
                    {
                        label: 'Registrations',
                        data: {!! json_encode($stats['trend_registrations']) !!},
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59,130,246,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Departures',
                        data: {!! json_encode($stats['trend_departures']) !!},
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ── Campus Bar Chart ──
    @if(!empty($stats['campus_names']))
    const campusCtx = document.getElementById('campusChart');
    if (campusCtx) {
        const campusPalette = ['#3B82F6','#10B981','#F59E0B','#8B5CF6','#EF4444','#06B6D4','#F97316','#84CC16'];
        new Chart(campusCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($stats['campus_names']) !!},
                datasets: [{
                    data: {!! json_encode($stats['campus_candidates']) !!},
                    backgroundColor: campusPalette.slice(0, {{ count($stats['campus_names']) }}),
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }
    @endif

    // ── Trade Pie Chart ──
    @if(!empty($stats['trade_names']))
    const tradeCtx = document.getElementById('tradeChart');
    if (tradeCtx) {
        const tradePalette = ['#3B82F6','#10B981','#F59E0B','#8B5CF6','#EF4444','#06B6D4','#F97316','#84CC16'];
        new Chart(tradeCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($stats['trade_names']) !!},
                datasets: [{
                    data: {!! json_encode($stats['trade_counts']) !!},
                    backgroundColor: tradePalette.slice(0, {{ count($stats['trade_names']) }}),
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { padding: 12, usePointStyle: true, font: { size: 11 } }
                    }
                }
            }
        });
    }
    @endif

});
</script>
@endpush
@endsection
