@extends('layouts.app')

@section('title', 'Remittance Details - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Details</h1>
            <p class="text-gray-600 mt-1">Transaction Reference: {{ $remittance->transaction_reference }}</p>
        </div>
        <div class="flex items-center space-x-3">
            @can('update', $remittance)
            <a href="{{ route('remittances.edit', $remittance) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-edit mr-2"></i>
                Edit
            </a>
            @endcan
            @can('delete', $remittance)
            <form action="{{ route('remittances.destroy', $remittance) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this remittance? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <i class="fas fa-trash mr-2"></i>
                    Delete
                </button>
            </form>
            @endcan
            <a href="{{ route('remittances.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to List
            </a>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-2">Current Status</p>
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ config('remittance.statuses.' . $remittance->status . '.class') }}">
                    {{ config('remittance.statuses.' . $remittance->status . '.label') }}
                </span>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">Proof of Transfer</p>
                <div class="flex items-center">
                    @if($remittance->has_proof)
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span class="text-sm font-medium text-green-700">{{ $remittance->receipts->count() }} document(s) uploaded</span>
                    @else
                    <i class="fas fa-times-circle text-red-500 mr-2"></i>
                    <span class="text-sm font-medium text-red-700">No proof uploaded</span>
                    @endif
                </div>
            </div>
            <div>
                @if($remittance->status === 'verified')
                <p class="text-sm text-gray-600 mb-2">Verified By</p>
                <div class="text-sm">
                    <p class="font-medium text-gray-900">{{ $remittance->verifiedBy->name }}</p>
                    <p class="text-gray-500">{{ $remittance->verified_at->format('M d, Y H:i') }}</p>
                </div>
                @elseif($remittance->status === 'pending')
                @can('verify', $remittance)
                <form action="{{ route('remittances.verify', $remittance) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Verify Remittance
                    </button>
                </form>
                @endcan
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Main Details -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Candidate Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user mr-2"></i>Candidate Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Full Name</p>
                        <p class="font-medium text-gray-900">{{ $remittance->candidate->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">CNIC</p>
                        <p class="font-medium text-gray-900">{{ $remittance->candidate->cnic }}</p>
                    </div>
                    @if($remittance->departure)
                    <div>
                        <p class="text-sm text-gray-600">Destination Country</p>
                        <p class="font-medium text-gray-900">{{ $remittance->departure->destination_country }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Deployment Date</p>
                        <p class="font-medium text-gray-900">{{ $remittance->departure->departure_date->format('M d, Y') }}</p>
                    </div>
                    @if($remittance->month_number)
                    <div>
                        <p class="text-sm text-gray-600">Month Number</p>
                        <p class="font-medium text-gray-900">Month {{ $remittance->month_number }} of deployment</p>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-receipt mr-2"></i>Transaction Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Transaction Reference</p>
                        <p class="font-medium text-gray-900">{{ $remittance->transaction_reference }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Transfer Date</p>
                        <p class="font-medium text-gray-900">{{ $remittance->transfer_date->format('M d, Y') }}</p>
                    </div>
                    @if($remittance->transfer_method)
                    <div>
                        <p class="text-sm text-gray-600">Transfer Method</p>
                        <p class="font-medium text-gray-900">{{ $remittance->transfer_method }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-600">Year / Month</p>
                        <p class="font-medium text-gray-900">{{ $remittance->year }} / {{ date('F', mktime(0, 0, 0, $remittance->month, 1)) }}</p>
                    </div>
                </div>
            </div>

            <!-- Amount Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-coins mr-2"></i>Amount Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Amount</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($remittance->amount, 2) }}</p>
                        <p class="text-sm text-gray-500">{{ $remittance->currency }}</p>
                    </div>
                    @if($remittance->amount_foreign)
                    <div>
                        <p class="text-sm text-gray-600">Foreign Amount</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($remittance->amount_foreign, 2) }}</p>
                        <p class="text-sm text-gray-500">{{ $remittance->foreign_currency }}</p>
                    </div>
                    @endif
                    @if($remittance->exchange_rate)
                    <div>
                        <p class="text-sm text-gray-600">Exchange Rate</p>
                        <p class="font-medium text-gray-900">1 {{ $remittance->foreign_currency }} = {{ number_format($remittance->exchange_rate, 4) }} {{ $remittance->currency }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sender Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user-check mr-2"></i>Sender Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Sender Name</p>
                        <p class="font-medium text-gray-900">{{ $remittance->sender_name }}</p>
                    </div>
                    @if($remittance->sender_location)
                    <div>
                        <p class="text-sm text-gray-600">Sender Location</p>
                        <p class="font-medium text-gray-900">{{ $remittance->sender_location }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Receiver Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user-tag mr-2"></i>Receiver Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Receiver Name</p>
                        <p class="font-medium text-gray-900">{{ $remittance->receiver_name }}</p>
                    </div>
                    @if($remittance->receiver_account)
                    <div>
                        <p class="text-sm text-gray-600">Receiver Account</p>
                        <p class="font-medium text-gray-900">{{ $remittance->receiver_account }}</p>
                    </div>
                    @endif
                    @if($remittance->bank_name)
                    <div>
                        <p class="text-sm text-gray-600">Bank Name</p>
                        <p class="font-medium text-gray-900">{{ $remittance->bank_name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Purpose -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bullseye mr-2"></i>Purpose
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Primary Purpose</p>
                        <p class="font-medium text-gray-900">{{ config('remittance.purposes.' . $remittance->primary_purpose) }}</p>
                    </div>
                    @if($remittance->purpose_description)
                    <div>
                        <p class="text-sm text-gray-600">Description</p>
                        <p class="text-gray-900">{{ $remittance->purpose_description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($remittance->notes)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-note-sticky mr-2"></i>Additional Notes
                </h2>
                <p class="text-gray-900 whitespace-pre-wrap">{{ $remittance->notes }}</p>
            </div>
            @endif

        </div>

        <!-- Right Column: Receipts & Actions -->
        <div class="space-y-6">

            <!-- Receipts Section -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-file-invoice mr-2"></i>Proof Documents
                </h2>

                @if($remittance->receipts->count() > 0)
                <div class="space-y-3 mb-4">
                    @foreach($remittance->receipts as $receipt)
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-medium text-sm text-gray-900">{{ $receipt->file_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $receipt->document_type }}</p>
                                <p class="text-xs text-gray-500">Uploaded by {{ $receipt->uploadedBy->name }}</p>
                                <p class="text-xs text-gray-500">{{ $receipt->created_at->format('M d, Y H:i') }}</p>
                                @if($receipt->is_verified)
                                <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded">Verified</span>
                                @endif
                            </div>
                            <div class="flex items-center space-x-2 ml-2">
                                <a href="{{ Storage::url($receipt->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-download"></i>
                                </a>
                                @can('delete', $remittance)
                                <form action="{{ route('remittances.delete-receipt', $receipt) }}" method="POST" class="inline" onsubmit="return confirm('Delete this receipt?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 mb-4">No proof documents uploaded yet.</p>
                @endif

                @can('update', $remittance)
                <form action="{{ route('remittances.upload-receipt', $remittance) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                        <select name="document_type" required class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                            <option value="">-- Select Type --</option>
                            @foreach(config('remittance.document_types') as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload File</label>
                        <input type="file" name="receipt" required accept=".pdf,.jpg,.jpeg,.png" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (Max 5MB)</p>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-upload mr-2"></i>Upload Document
                    </button>
                </form>
                @endcan
            </div>

            <!-- Usage Breakdown -->
            @if($remittance->usageBreakdown->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-chart-pie mr-2"></i>Usage Breakdown
                </h2>
                <div class="space-y-3">
                    @foreach($remittance->usageBreakdown as $usage)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $usage->category_label }}</span>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($usage->amount, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $usage->percentage }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $usage->percentage }}%</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Activity Log -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-history mr-2"></i>Activity Log
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-600">Recorded By</p>
                        <p class="text-sm font-medium text-gray-900">{{ $remittance->recordedBy->name }}</p>
                        <p class="text-xs text-gray-500">{{ $remittance->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    @if($remittance->status === 'verified' && $remittance->verifiedBy)
                    <div>
                        <p class="text-xs text-gray-600">Verified By</p>
                        <p class="text-sm font-medium text-gray-900">{{ $remittance->verifiedBy->name }}</p>
                        <p class="text-xs text-gray-500">{{ $remittance->verified_at->format('M d, Y H:i') }}</p>
                    </div>
                    @endif
                    @if($remittance->updated_at != $remittance->created_at)
                    <div>
                        <p class="text-xs text-gray-600">Last Updated</p>
                        <p class="text-xs text-gray-500">{{ $remittance->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
