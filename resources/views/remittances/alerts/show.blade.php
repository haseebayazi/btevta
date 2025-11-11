@extends('layouts.app')

@section('title', 'Alert Details - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Alert Details</h1>
            <p class="text-gray-600 mt-1">{{ $alert->alert_type_label }}</p>
        </div>
        <a href="{{ route('remittance.alerts.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Alerts
        </a>
    </div>

    <!-- Alert Card -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 {{ $alert->severity === 'critical' ? 'bg-red-50' : ($alert->severity === 'warning' ? 'bg-yellow-50' : 'bg-blue-50') }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $alert->severity_badge_class }}">
                        {{ ucfirst($alert->severity) }}
                    </span>
                    @if(!$alert->is_read)
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">New</span>
                    @endif
                    @if($alert->is_resolved)
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Resolved</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600">Created {{ $alert->created_at->format('M d, Y H:i') }}</p>
            </div>
        </div>

        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ $alert->title }}</h2>
            <p class="text-gray-700 leading-relaxed mb-6">{{ $alert->message }}</p>

            <!-- Alert Metadata -->
            @if($alert->metadata)
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Additional Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($alert->metadata as $key => $value)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                        <span class="text-sm font-medium text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Candidate Information -->
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user mr-2"></i>Candidate Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Full Name</p>
                        <p class="font-medium text-gray-900">{{ $alert->candidate->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">CNIC</p>
                        <p class="font-medium text-gray-900">{{ $alert->candidate->cnic }}</p>
                    </div>
                    @if($alert->candidate->phone)
                    <div>
                        <p class="text-sm text-gray-600">Phone</p>
                        <p class="font-medium text-gray-900">{{ $alert->candidate->phone }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-600">Actions</p>
                        <a href="{{ route('candidates.show', $alert->candidate) }}" class="text-blue-600 hover:text-blue-700 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>View Candidate Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Remittance Information (if applicable) -->
            @if($alert->remittance)
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    <i class="fas fa-receipt mr-2"></i>Related Remittance
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Transaction Reference</p>
                        <p class="font-medium text-gray-900">{{ $alert->remittance->transaction_reference }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Amount</p>
                        <p class="font-medium text-gray-900">PKR {{ number_format($alert->remittance->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Transfer Date</p>
                        <p class="font-medium text-gray-900">{{ $alert->remittance->transfer_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded {{ config('remittance.statuses.' . $alert->remittance->status . '.class') }}">
                            {{ config('remittance.statuses.' . $alert->remittance->status . '.label') }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Actions</p>
                        <a href="{{ route('remittances.show', $alert->remittance) }}" class="text-blue-600 hover:text-blue-700 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>View Remittance Details
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Resolution Information -->
            @if($alert->is_resolved)
            <div class="border-t border-gray-200 pt-6 bg-green-50 -m-6 mt-0 p-6">
                <h3 class="font-semibold text-green-900 mb-4">
                    <i class="fas fa-check-circle mr-2"></i>Resolution Details
                </h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-green-600">Resolved By</p>
                        <p class="font-medium text-green-900">{{ $alert->resolvedBy->name ?? 'System' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-green-600">Resolved At</p>
                        <p class="font-medium text-green-900">{{ $alert->resolved_at->format('M d, Y H:i') }}</p>
                    </div>
                    @if($alert->resolution_notes)
                    <div>
                        <p class="text-sm text-green-600">Resolution Notes</p>
                        <p class="font-medium text-green-900">{{ $alert->resolution_notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    @if(!$alert->is_resolved)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>

        <div class="space-y-4">
            <!-- Resolve Form -->
            <form action="{{ route('remittance.alerts.resolve', $alert) }}" method="POST" class="border border-gray-200 rounded-lg p-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-2">Resolve Alert</label>
                <textarea name="resolution_notes" rows="3" placeholder="Enter resolution notes (optional)"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-3"></textarea>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>Mark as Resolved
                </button>
            </form>

            <!-- Quick Actions -->
            <div class="flex items-center space-x-3">
                <form action="{{ route('remittance.alerts.dismiss', $alert) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg" onclick="return confirm('Dismiss this alert?')">
                        <i class="fas fa-times-circle mr-2"></i>Dismiss Alert
                    </button>
                </form>

                @if(!$alert->is_read)
                <form action="{{ route('remittance.alerts.read', $alert) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-eye mr-2"></i>Mark as Read
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
