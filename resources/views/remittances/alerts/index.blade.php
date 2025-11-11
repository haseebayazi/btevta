@extends('layouts.app')

@section('title', 'Remittance Alerts - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Alerts</h1>
            <p class="text-gray-600 mt-1">Automated alerts for remittance issues and anomalies</p>
        </div>
        <div class="flex items-center space-x-3">
            @if(Auth::user()->role === 'admin')
            <button onclick="document.getElementById('generate-form').submit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-sync mr-2"></i>
                Generate Alerts
            </button>
            <form id="generate-form" action="{{ route('remittance.alerts.generate') }}" method="POST" class="hidden">
                @csrf
            </form>

            <button onclick="document.getElementById('auto-resolve-form').submit()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-check-double mr-2"></i>
                Auto-Resolve
            </button>
            <form id="auto-resolve-form" action="{{ route('remittance.alerts.auto-resolve') }}" method="POST" class="hidden">
                @csrf
            </form>
            @endif

            <a href="{{ route('remittances.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Remittances
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <p class="text-gray-600 text-sm font-medium">Critical Alerts</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $stats['critical_alerts'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Requires immediate attention</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <p class="text-gray-600 text-sm font-medium">Unresolved Alerts</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['unresolved_alerts'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending action</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-medium">Unread Alerts</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['unread_alerts'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">New notifications</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-gray-500">
            <p class="text-gray-600 text-sm font-medium">Total Alerts</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_alerts'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">All time</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="GET" action="{{ route('remittance.alerts.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All</option>
                    <option value="unresolved" {{ request('status') == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
                <select name="severity" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Severities</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Types</option>
                    <option value="missing_remittance" {{ request('type') == 'missing_remittance' ? 'selected' : '' }}>Missing Remittance</option>
                    <option value="missing_proof" {{ request('type') == 'missing_proof' ? 'selected' : '' }}>Missing Proof</option>
                    <option value="first_remittance_delay" {{ request('type') == 'first_remittance_delay' ? 'selected' : '' }}>First Remittance Delay</option>
                    <option value="low_frequency" {{ request('type') == 'low_frequency' ? 'selected' : '' }}>Low Frequency</option>
                    <option value="unusual_amount" {{ request('type') == 'unusual_amount' ? 'selected' : '' }}>Unusual Amount</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Read Status</label>
                <select name="read" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All</option>
                    <option value="unread" {{ request('read') == 'unread' ? 'selected' : '' }}>Unread</option>
                    <option value="read" {{ request('read') == 'read' ? 'selected' : '' }}>Read</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Alerts List -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Alerts</h2>
            @if($alerts->total() > 0)
            <form action="{{ route('remittance.alerts.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm">
                    <i class="fas fa-check-double mr-1"></i>Mark All as Read
                </button>
            </form>
            @endif
        </div>

        @if($alerts->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($alerts as $alert)
            <div class="p-6 hover:bg-gray-50 transition {{ !$alert->is_read ? 'bg-blue-50' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <!-- Severity Badge -->
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $alert->severity_badge_class }}">
                                {{ ucfirst($alert->severity) }}
                            </span>

                            <!-- Alert Type -->
                            <span class="text-sm text-gray-600">{{ $alert->alert_type_label }}</span>

                            <!-- Unread Badge -->
                            @if(!$alert->is_read)
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-semibold rounded">New</span>
                            @endif

                            <!-- Resolved Badge -->
                            @if($alert->is_resolved)
                            <span class="px-2 py-0.5 bg-green-100 text-green-800 text-xs font-semibold rounded">Resolved</span>
                            @endif
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $alert->title }}</h3>
                        <p class="text-gray-700 mb-3">{{ $alert->message }}</p>

                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span><i class="fas fa-user mr-1"></i>{{ $alert->candidate->full_name }}</span>
                            <span><i class="fas fa-clock mr-1"></i>{{ $alert->created_at->diffForHumans() }}</span>
                            @if($alert->remittance)
                            <span><i class="fas fa-receipt mr-1"></i>{{ $alert->remittance->transaction_reference }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="ml-4 flex flex-col space-y-2">
                        <a href="{{ route('remittance.alerts.show', $alert) }}" class="text-blue-600 hover:text-blue-700 text-sm whitespace-nowrap">
                            <i class="fas fa-eye mr-1"></i>View Details
                        </a>

                        @if(!$alert->is_resolved)
                        <form action="{{ route('remittance.alerts.dismiss', $alert) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-700 text-sm whitespace-nowrap" onclick="return confirm('Dismiss this alert?')">
                                <i class="fas fa-times-circle mr-1"></i>Dismiss
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($alerts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $alerts->links() }}
        </div>
        @endif

        @else
        <div class="p-12 text-center text-gray-500">
            <i class="fas fa-bell-slash text-6xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Alerts Found</h3>
            <p>There are no alerts matching your current filters.</p>
        </div>
        @endif
    </div>

</div>
@endsection
