@extends('layouts.app')
@section('title', 'Post-Departure Dashboard')
@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><i class="fas fa-globe mr-2 text-blue-600"></i>Post-Departure Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Comprehensive post-departure tracking: residency, employment, and compliance</p>
        </div>

        @can('viewAny', \App\Models\PostDepartureDetail::class)
        @if(!auth()->user()->isCampusAdmin())
        <form method="GET" class="flex items-center gap-2">
            <select name="campus_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Campuses</option>
                @foreach($campuses as $campus)
                <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                    {{ $campus->name }}
                </option>
                @endforeach
            </select>
        </form>
        @endif
        @endcan
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Total Abroad</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $dashboard['summary']['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
            <p class="text-xs font-semibold text-green-600 uppercase tracking-wider">Compliance Verified</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $dashboard['summary']['compliance_verified'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-yellow-500">
            <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wider">Compliance Pending</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $dashboard['summary']['compliance_pending'] }}</p>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Iqama Status -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-id-card mr-2 text-blue-500"></i>Iqama Status</h3>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Pending</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        {{ $dashboard['iqama_status']['pending'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Issued</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $dashboard['iqama_status']['issued'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Expiring Soon</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $dashboard['iqama_status']['expiring'] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Tracking App (Absher) -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-mobile-alt mr-2 text-blue-500"></i>Tracking App (Absher)</h3>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Registered</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $dashboard['tracking_app']['registered'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Pending</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        {{ $dashboard['tracking_app']['pending'] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- WPS Registration -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-money-check-alt mr-2 text-blue-500"></i>WPS Registration</h3>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Registered</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $dashboard['wps']['registered'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Pending</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        {{ $dashboard['wps']['pending'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Company Switches -->
    @if($dashboard['recent_switches']->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-exchange-alt mr-2 text-blue-500"></i>Recent Company Switches</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidate</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Company</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Company</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Switch #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($dashboard['recent_switches'] as $switch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $switch->candidate?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $switch->fromEmployment?->company_name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $switch->toEmployment?->company_name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $switch->switch_number }}</td>
                        <td class="px-4 py-3">
                            @php
                                $switchColor = match($switch->status->value) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $switchColor }}">
                                {{ $switch->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $switch->switch_date->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Candidates List -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-users mr-2 text-blue-500"></i>Abroad Candidates</h3>
        </div>
        @if($dashboard['candidates']->isEmpty())
        <div class="px-5 py-10 text-center text-gray-500">
            <i class="fas fa-globe text-4xl mb-3 text-gray-300"></i>
            <p class="text-sm">No post-departure records found.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidate</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Iqama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Employer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking App</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WPS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($dashboard['candidates'] as $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $detail->candidate?->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $detail->candidate?->cnic ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $detail->candidate?->campus?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">
                            @if($detail->iqama_status)
                            @php
                                $iqamaColor = match($detail->iqama_status->value) {
                                    'issued' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'expired' => 'bg-red-100 text-red-800',
                                    'renewed' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $iqamaColor }}">
                                {{ $detail->iqama_status->label() }}
                            </span>
                            @if($detail->iqama_expiring)
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Expiring
                            </span>
                            @endif
                            @else
                            <span class="text-xs text-gray-400">Not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $detail->currentEmployment?->company_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($detail->tracking_app_registered)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>Registered
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($detail->wps_registered)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>Registered
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($detail->compliance_verified)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Verified
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($detail->candidate)
                            <a href="{{ route('post-departure.show', $detail->candidate) }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
