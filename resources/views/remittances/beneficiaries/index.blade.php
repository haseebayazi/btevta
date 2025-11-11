@extends('layouts.app')

@section('title', 'Remittance Beneficiaries - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Remittance Beneficiaries</h1>
            <p class="text-gray-600 mt-1">Manage remittance receivers for {{ $candidate->full_name }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('beneficiaries.create', $candidate->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Beneficiary
            </a>
            <a href="{{ route('candidates.show', $candidate->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Candidate
            </a>
        </div>
    </div>

    <!-- Candidate Info Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-start">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-user text-blue-600 text-2xl"></i>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-gray-900">{{ $candidate->full_name }}</h2>
                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">CNIC:</span>
                        <span class="font-medium text-gray-900 ml-2">{{ $candidate->cnic }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-medium text-gray-900 ml-2">{{ $candidate->phone ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Total Beneficiaries:</span>
                        <span class="font-medium text-gray-900 ml-2">{{ $beneficiaries->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($beneficiaries->count() > 0)
    <!-- Beneficiaries Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($beneficiaries as $beneficiary)
        <div class="bg-white rounded-lg shadow-sm border-l-4 {{ $beneficiary->is_primary ? 'border-green-500' : 'border-gray-300' }}">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $beneficiary->full_name }}</h3>
                            @if($beneficiary->is_primary)
                            <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs font-semibold rounded">Primary</span>
                            @endif
                            @if(!$beneficiary->is_active)
                            <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 text-xs font-semibold rounded">Inactive</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ ucfirst($beneficiary->relationship) }}</p>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="space-y-2 mb-4">
                    @if($beneficiary->cnic)
                    <div class="flex items-start text-sm">
                        <i class="fas fa-id-card text-gray-400 w-5 mt-0.5"></i>
                        <div class="ml-2">
                            <span class="text-gray-600">CNIC:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->cnic }}</span>
                        </div>
                    </div>
                    @endif

                    @if($beneficiary->phone)
                    <div class="flex items-start text-sm">
                        <i class="fas fa-phone text-gray-400 w-5 mt-0.5"></i>
                        <div class="ml-2">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->phone }}</span>
                        </div>
                    </div>
                    @endif

                    @if($beneficiary->email)
                    <div class="flex items-start text-sm">
                        <i class="fas fa-envelope text-gray-400 w-5 mt-0.5"></i>
                        <div class="ml-2">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->email }}</span>
                        </div>
                    </div>
                    @endif

                    @if($beneficiary->address)
                    <div class="flex items-start text-sm">
                        <i class="fas fa-map-marker-alt text-gray-400 w-5 mt-0.5"></i>
                        <div class="ml-2">
                            <span class="text-gray-600">Address:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->address }}</span>
                            @if($beneficiary->city || $beneficiary->district)
                            <span class="text-gray-600 ml-1">({{ $beneficiary->city }}{{ $beneficiary->district ? ', ' . $beneficiary->district : '' }})</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Banking Information -->
                @if($beneficiary->bank_name || $beneficiary->account_number || $beneficiary->iban || $beneficiary->mobile_wallet)
                <div class="border-t border-gray-200 pt-4 mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">
                        <i class="fas fa-university mr-1"></i>Banking Details
                    </h4>
                    <div class="space-y-1 text-sm">
                        @if($beneficiary->bank_name)
                        <div>
                            <span class="text-gray-600">Bank:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->bank_name }}</span>
                        </div>
                        @endif
                        @if($beneficiary->account_number)
                        <div>
                            <span class="text-gray-600">Account:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->account_number }}</span>
                        </div>
                        @endif
                        @if($beneficiary->iban)
                        <div>
                            <span class="text-gray-600">IBAN:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->iban }}</span>
                        </div>
                        @endif
                        @if($beneficiary->mobile_wallet)
                        <div>
                            <span class="text-gray-600">Mobile Wallet:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $beneficiary->mobile_wallet }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Notes -->
                @if($beneficiary->notes)
                <div class="border-t border-gray-200 pt-4 mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">Notes</h4>
                    <p class="text-sm text-gray-600">{{ $beneficiary->notes }}</p>
                </div>
                @endif

                <!-- Actions -->
                <div class="border-t border-gray-200 pt-4 flex items-center justify-end space-x-2">
                    @if(!$beneficiary->is_primary)
                    <form action="{{ route('beneficiaries.set-primary', $beneficiary) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-green-600 hover:text-green-900 px-3 py-1 border border-green-600 rounded">
                            <i class="fas fa-star mr-1"></i>Set Primary
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="text-sm text-blue-600 hover:text-blue-900 px-3 py-1 border border-blue-600 rounded">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <form action="{{ route('beneficiaries.destroy', $beneficiary) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this beneficiary?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-900 px-3 py-1 border border-red-600 rounded">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </form>
                </div>

                <!-- Metadata -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500">Added: {{ $beneficiary->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @else
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Beneficiaries Added</h3>
        <p class="text-gray-600 mb-6">Add family members or beneficiaries who will receive remittances from this candidate.</p>
        <a href="{{ route('beneficiaries.create', $candidate->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Add First Beneficiary
        </a>
    </div>
    @endif

</div>
@endsection
