<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-university mr-2 text-blue-500"></i>Foreign Bank Account</h3>
    </div>
    <div class="px-5 py-4">
        @if($detail->foreign_bank_account)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5 p-4 bg-gray-50 rounded-lg">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Bank Name</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $detail->foreign_bank_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Account Number</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $detail->foreign_bank_account }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">IBAN</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $detail->foreign_bank_iban ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">SWIFT</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $detail->foreign_bank_swift ?? 'N/A' }}</p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('post-departure.update-bank', $detail) }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_name"
                           value="{{ old('bank_name', $detail->foreign_bank_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('bank_name') border-red-500 @enderror"
                           required>
                    @error('bank_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Number <span class="text-red-500">*</span></label>
                    <input type="text" name="account_number"
                           value="{{ old('account_number', $detail->foreign_bank_account) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('account_number') border-red-500 @enderror"
                           required>
                    @error('account_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                    <input type="text" name="iban"
                           value="{{ old('iban', $detail->foreign_bank_iban) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SWIFT</label>
                    <input type="text" name="swift"
                           value="{{ old('swift', $detail->foreign_bank_swift) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Evidence</label>
                    <input type="file" name="evidence"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-1.5 @error('evidence') border-red-500 @enderror">
                    @error('evidence')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Bank Details
                </button>
            </div>
        </form>
    </div>
</div>
