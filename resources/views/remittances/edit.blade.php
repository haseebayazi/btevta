@extends('layouts.app')

@section('title', 'Edit Remittance - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Remittance</h1>
            <p class="text-gray-600 mt-1">Update remittance details for {{ $remittance->candidate?->full_name ?? 'Unknown Candidate' }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('remittances.show', $remittance) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-eye mr-2"></i>
                View Details
            </a>
            <a href="{{ route('remittances.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to List
            </a>
        </div>
    </div>

    @if($remittance->status === 'verified')
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    This remittance has been verified by {{ $remittance->verifiedBy?->name ?? 'System' }} on {{ $remittance->verified_at?->format('M d, Y') ?? 'N/A' }}.
                    Changes may require re-verification.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Form -->
    <form action="{{ route('remittances.update', $remittance) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Candidate Selection -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user mr-2"></i>Candidate Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select Candidate <span class="text-red-500">*</span>
                    </label>
                    <select name="candidate_id" id="candidate_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('candidate_id') border-red-500 @enderror">
                        <option value="">-- Select Candidate --</option>
                        @foreach($candidates as $candidate)
                        <option value="{{ $candidate->id }}"
                                data-departure-id="{{ $candidate->departure->id ?? '' }}"
                                {{ (old('candidate_id', $remittance->candidate_id) == $candidate->id) ? 'selected' : '' }}>
                            {{ $candidate->full_name }} ({{ $candidate->cnic }}) - {{ $candidate->departure->destination_country ?? 'N/A' }}
                        </option>
                        @endforeach
                    </select>
                    @error('candidate_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Beneficiary (Optional)
                    </label>
                    <select name="beneficiary_id" id="beneficiary_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Select Beneficiary --</option>
                        @foreach($beneficiaries as $beneficiary)
                        <option value="{{ $beneficiary->id }}"
                                data-full-name="{{ $beneficiary->full_name }}"
                                data-account-number="{{ $beneficiary->account_number ?? $beneficiary->iban ?? $beneficiary->mobile_wallet ?? '' }}"
                                data-bank-name="{{ $beneficiary->bank_name ?? '' }}">
                            {{ $beneficiary->full_name }} ({{ $beneficiary->relationship }})
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select to auto-fill receiver details</p>
                </div>

                <input type="hidden" name="departure_id" id="departure_id" value="{{ old('departure_id', $remittance->departure_id) }}">
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-receipt mr-2"></i>Transaction Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Transaction Reference <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="transaction_reference" value="{{ old('transaction_reference', $remittance->transaction_reference) }}" required
                           placeholder="e.g., TXN-2024-001234"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('transaction_reference') border-red-500 @enderror">
                    @error('transaction_reference')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Transfer Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="transfer_date" value="{{ old('transfer_date', $remittance->transfer_date?->format('Y-m-d') ?? '') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('transfer_date') border-red-500 @enderror">
                    @error('transfer_date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Transfer Method
                    </label>
                    <select name="transfer_method"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('transfer_method') border-red-500 @enderror">
                        <option value="">-- Select Method --</option>
                        @foreach(config('remittance.transfer_methods') as $method)
                        <option value="{{ $method }}" {{ old('transfer_method', $remittance->transfer_method) == $method ? 'selected' : '' }}>{{ $method }}</option>
                        @endforeach
                    </select>
                    @error('transfer_method')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Primary Purpose <span class="text-red-500">*</span>
                    </label>
                    <select name="primary_purpose" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('primary_purpose') border-red-500 @enderror">
                        <option value="">-- Select Purpose --</option>
                        @foreach(config('remittance.purposes') as $key => $label)
                        <option value="{{ $key }}" {{ old('primary_purpose', $remittance->primary_purpose) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('primary_purpose')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Purpose Description
                    </label>
                    <textarea name="purpose_description" rows="2"
                              placeholder="Additional details about the purpose of this remittance"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('purpose_description') border-red-500 @enderror">{{ old('purpose_description', $remittance->purpose_description) }}</textarea>
                    @error('purpose_description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Amount Details -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-coins mr-2"></i>Amount Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Amount (PKR) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ old('amount', $remittance->amount) }}" required step="0.01" min="0"
                           placeholder="0.00"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('amount') border-red-500 @enderror">
                    @error('amount')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Currency
                    </label>
                    <select name="currency"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('currency') border-red-500 @enderror">
                        @foreach(config('remittance.currencies') as $curr)
                        <option value="{{ $curr }}" {{ old('currency', $remittance->currency) == $curr ? 'selected' : '' }}>{{ $curr }}</option>
                        @endforeach
                    </select>
                    @error('currency')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <input type="checkbox" id="has_foreign_currency" {{ old('amount_foreign', $remittance->amount_foreign) ? 'checked' : '' }}>
                        Foreign Currency Amount
                    </label>
                    <input type="number" name="amount_foreign" id="amount_foreign" value="{{ old('amount_foreign', $remittance->amount_foreign) }}"
                           step="0.01" min="0" placeholder="0.00"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('amount_foreign') border-red-500 @enderror"
                           {{ old('amount_foreign', $remittance->amount_foreign) ? '' : 'disabled' }}>
                    @error('amount_foreign')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Foreign Currency
                    </label>
                    <select name="foreign_currency" id="foreign_currency"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('foreign_currency') border-red-500 @enderror"
                            {{ old('amount_foreign', $remittance->amount_foreign) ? '' : 'disabled' }}>
                        <option value="">-- Select --</option>
                        @foreach(config('remittance.currencies') as $curr)
                        <option value="{{ $curr }}" {{ old('foreign_currency', $remittance->foreign_currency) == $curr ? 'selected' : '' }}>{{ $curr }}</option>
                        @endforeach
                    </select>
                    @error('foreign_currency')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Exchange Rate
                    </label>
                    <input type="number" name="exchange_rate" id="exchange_rate" value="{{ old('exchange_rate', $remittance->exchange_rate) }}"
                           step="0.0001" min="0" placeholder="0.0000"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('exchange_rate') border-red-500 @enderror"
                           {{ old('amount_foreign', $remittance->amount_foreign) ? '' : 'disabled' }}>
                    @error('exchange_rate')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Sender Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user-check mr-2"></i>Sender Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sender Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name', $remittance->sender_name) }}" required
                           placeholder="Full name of sender"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('sender_name') border-red-500 @enderror">
                    @error('sender_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sender Location
                    </label>
                    <input type="text" name="sender_location" value="{{ old('sender_location', $remittance->sender_location) }}"
                           placeholder="City, Country"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('sender_location') border-red-500 @enderror">
                    @error('sender_location')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Receiver Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user-tag mr-2"></i>Receiver Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Receiver Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="receiver_name" id="receiver_name" value="{{ old('receiver_name', $remittance->receiver_name) }}" required
                           placeholder="Full name of receiver"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('receiver_name') border-red-500 @enderror">
                    @error('receiver_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Receiver Account
                    </label>
                    <input type="text" name="receiver_account" id="receiver_account" value="{{ old('receiver_account', $remittance->receiver_account) }}"
                           placeholder="Account number or IBAN"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('receiver_account') border-red-500 @enderror">
                    @error('receiver_account')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Bank Name
                    </label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $remittance->bank_name) }}"
                           placeholder="Name of bank or financial institution"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('bank_name') border-red-500 @enderror">
                    @error('bank_name')
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
                          placeholder="Any additional information or notes about this remittance"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 @error('notes') border-red-500 @enderror">{{ old('notes', $remittance->notes) }}</textarea>
                @error('notes')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('remittances.show', $remittance) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-save mr-2"></i>Update Remittance
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const candidateSelect = document.getElementById('candidate_id');
    const beneficiarySelect = document.getElementById('beneficiary_id');
    const departureIdInput = document.getElementById('departure_id');
    const senderNameInput = document.getElementById('sender_name');
    const receiverNameInput = document.getElementById('receiver_name');
    const receiverAccountInput = document.getElementById('receiver_account');
    const bankNameInput = document.getElementById('bank_name');

    const hasForeignCurrencyCheckbox = document.getElementById('has_foreign_currency');
    const amountForeignInput = document.getElementById('amount_foreign');
    const foreignCurrencySelect = document.getElementById('foreign_currency');
    const exchangeRateInput = document.getElementById('exchange_rate');

    // Load beneficiaries when candidate is selected
    candidateSelect.addEventListener('change', function() {
        const candidateId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const departureId = selectedOption.getAttribute('data-departure-id');

        // Set departure ID
        departureIdInput.value = departureId;

        // Clear and load beneficiaries
        beneficiarySelect.innerHTML = '<option value="">-- Select Beneficiary --</option>';

        if (candidateId) {
            fetch(`/candidates/${candidateId}/beneficiaries/data`)
                .then(response => response.json())
                .then(beneficiaries => {
                    beneficiaries.forEach(beneficiary => {
                        const option = document.createElement('option');
                        option.value = beneficiary.id;
                        option.textContent = `${beneficiary.full_name} (${beneficiary.relationship})`;
                        option.dataset.fullName = beneficiary.full_name;
                        option.dataset.accountNumber = beneficiary.account_number || beneficiary.iban || beneficiary.mobile_wallet || '';
                        option.dataset.bankName = beneficiary.bank_name || '';
                        beneficiarySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading beneficiaries:', error));
        }
    });

    // Auto-fill receiver details when beneficiary is selected
    beneficiarySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            receiverNameInput.value = selectedOption.dataset.fullName || '';
            receiverAccountInput.value = selectedOption.dataset.accountNumber || '';
            bankNameInput.value = selectedOption.dataset.bankName || '';
        }
    });

    // Toggle foreign currency fields
    hasForeignCurrencyCheckbox.addEventListener('change', function() {
        const isEnabled = this.checked;
        amountForeignInput.disabled = !isEnabled;
        foreignCurrencySelect.disabled = !isEnabled;
        exchangeRateInput.disabled = !isEnabled;

        if (!isEnabled) {
            amountForeignInput.value = '';
            foreignCurrencySelect.value = '';
            exchangeRateInput.value = '';
        }
    });

    // Auto-calculate exchange rate when both amounts are provided
    const amountInput = document.querySelector('input[name="amount"]');

    function calculateExchangeRate() {
        const pkrAmount = parseFloat(amountInput.value);
        const foreignAmount = parseFloat(amountForeignInput.value);

        if (pkrAmount && foreignAmount && pkrAmount > 0 && foreignAmount > 0) {
            const rate = pkrAmount / foreignAmount;
            exchangeRateInput.value = rate.toFixed(4);
        }
    }

    amountInput.addEventListener('input', calculateExchangeRate);
    amountForeignInput.addEventListener('input', calculateExchangeRate);
});
</script>
@endpush
@endsection
