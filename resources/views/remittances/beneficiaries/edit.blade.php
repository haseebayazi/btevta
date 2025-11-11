@extends('layouts.app')

@section('title', 'Edit Beneficiary - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Beneficiary</h1>
            <p class="text-gray-600 mt-1">Update beneficiary details for {{ $candidate->full_name }}</p>
        </div>
        <a href="{{ route('beneficiaries.index', $candidate->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to List
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('beneficiaries.update', $beneficiary) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Personal Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user mr-2"></i>Personal Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="full_name" value="{{ old('full_name', $beneficiary->full_name) }}" required
                           placeholder="Full name of beneficiary"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('full_name') border-red-500 @enderror">
                    @error('full_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Relationship <span class="text-red-500">*</span>
                    </label>
                    <select name="relationship" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('relationship') border-red-500 @enderror">
                        <option value="">-- Select Relationship --</option>
                        @foreach(config('remittance.relationships') as $relationship)
                        <option value="{{ $relationship }}" {{ old('relationship', $beneficiary->relationship) == $relationship ? 'selected' : '' }}>{{ ucfirst($relationship) }}</option>
                        @endforeach
                    </select>
                    @error('relationship')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        CNIC
                    </label>
                    <input type="text" name="cnic" value="{{ old('cnic', $beneficiary->cnic) }}"
                           placeholder="xxxxx-xxxxxxx-x"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('cnic') border-red-500 @enderror">
                    @error('cnic')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input type="text" name="phone" value="{{ old('phone', $beneficiary->phone) }}"
                           placeholder="+92-xxx-xxxxxxx"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('phone') border-red-500 @enderror">
                    @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input type="email" name="email" value="{{ old('email', $beneficiary->email) }}"
                           placeholder="email@example.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('email') border-red-500 @enderror">
                    @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_primary" value="1" {{ old('is_primary', $beneficiary->is_primary) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Set as Primary Beneficiary</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $beneficiary->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                    <p class="text-xs text-gray-500">Inactive beneficiaries won't appear in remittance forms</p>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-map-marker-alt mr-2"></i>Address Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Address
                    </label>
                    <textarea name="address" rows="2"
                              placeholder="Street address"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('address') border-red-500 @enderror">{{ old('address', $beneficiary->address) }}</textarea>
                    @error('address')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        City
                    </label>
                    <input type="text" name="city" value="{{ old('city', $beneficiary->city) }}"
                           placeholder="City name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('city') border-red-500 @enderror">
                    @error('city')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        District
                    </label>
                    <input type="text" name="district" value="{{ old('district', $beneficiary->district) }}"
                           placeholder="District name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('district') border-red-500 @enderror">
                    @error('district')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Banking Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-university mr-2"></i>Banking Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Bank Name
                    </label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $beneficiary->bank_name) }}"
                           placeholder="e.g., HBL, UBL, MCB"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('bank_name') border-red-500 @enderror">
                    @error('bank_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Account Number
                    </label>
                    <input type="text" name="account_number" value="{{ old('account_number', $beneficiary->account_number) }}"
                           placeholder="Account number"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('account_number') border-red-500 @enderror">
                    @error('account_number')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        IBAN
                    </label>
                    <input type="text" name="iban" value="{{ old('iban', $beneficiary->iban) }}"
                           placeholder="PKxx xxxx xxxx xxxx xxxx xxxx xxxx"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('iban') border-red-500 @enderror">
                    @error('iban')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mobile Wallet
                    </label>
                    <input type="text" name="mobile_wallet" value="{{ old('mobile_wallet', $beneficiary->mobile_wallet) }}"
                           placeholder="e.g., JazzCash, Easypaisa number"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('mobile_wallet') border-red-500 @enderror">
                    @error('mobile_wallet')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Additional Notes -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-note-sticky mr-2"></i>Additional Notes
            </h2>

            <div>
                <textarea name="notes" rows="4"
                          placeholder="Any additional information about this beneficiary"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('notes') border-red-500 @enderror">{{ old('notes', $beneficiary->notes) }}</textarea>
                @error('notes')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('beneficiaries.index', $candidate->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-save mr-2"></i>Update Beneficiary
            </button>
        </div>
    </form>

</div>
@endsection
