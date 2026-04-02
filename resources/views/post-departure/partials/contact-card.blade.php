<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-phone mr-2 text-blue-500"></i>Foreign Contact Details</h3>
    </div>
    <div class="px-5 py-4">
        @if($detail->foreign_mobile_number)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 p-4 bg-gray-50 rounded-lg">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Mobile Number</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $detail->foreign_mobile_number }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Carrier</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $detail->foreign_mobile_carrier ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Address</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $detail->foreign_address ?? 'N/A' }}</p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('post-departure.update-contact', $detail) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                    <input type="text" name="mobile_number"
                           value="{{ old('mobile_number', $detail->foreign_mobile_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('mobile_number') border-red-500 @enderror"
                           required>
                    @error('mobile_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carrier</label>
                    <input type="text" name="carrier"
                           value="{{ old('carrier', $detail->foreign_mobile_carrier) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('carrier') border-red-500 @enderror">
                    @error('carrier')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror">{{ old('address', $detail->foreign_address) }}</textarea>
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Contact
                </button>
            </div>
        </form>
    </div>
</div>
