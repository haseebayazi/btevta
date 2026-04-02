<div class="bg-white rounded-lg shadow">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-id-card mr-2 text-blue-500"></i>Iqama / Residency</h3>
    </div>
    <div class="px-5 py-4">
        @if($detail->iqama_number)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5 p-4 bg-gray-50 rounded-lg">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Iqama Number</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $detail->iqama_number }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Issue Date</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $detail->iqama_issue_date?->format('d M Y') ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Expiry Date</p>
                <p class="text-sm text-gray-900 mt-0.5">
                    {{ $detail->iqama_expiry_date?->format('d M Y') ?? 'N/A' }}
                    @if($detail->iqama_expiring)
                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Expiring Soon</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                <div class="mt-0.5">
                    @php
                        $iqamaColor = match($detail->iqama_status?->value) {
                            'issued' => 'bg-green-100 text-green-800',
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'expired' => 'bg-red-100 text-red-800',
                            'renewed' => 'bg-blue-100 text-blue-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $iqamaColor }}">
                        {{ $detail->iqama_status?->label() ?? 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('post-departure.update-iqama', $detail) }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Iqama Number <span class="text-red-500">*</span></label>
                    <input type="text" name="iqama_number"
                           value="{{ old('iqama_number', $detail->iqama_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('iqama_number') border-red-500 @enderror"
                           required>
                    @error('iqama_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date <span class="text-red-500">*</span></label>
                    <input type="date" name="iqama_issue_date"
                           value="{{ old('iqama_issue_date', $detail->iqama_issue_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('iqama_issue_date') border-red-500 @enderror"
                           required>
                    @error('iqama_issue_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date <span class="text-red-500">*</span></label>
                    <input type="date" name="iqama_expiry_date"
                           value="{{ old('iqama_expiry_date', $detail->iqama_expiry_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('iqama_expiry_date') border-red-500 @enderror"
                           required>
                    @error('iqama_expiry_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="iqama_status"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('iqama_status') border-red-500 @enderror"
                            required>
                        @foreach(\App\Enums\IqamaStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ old('iqama_status', $detail->iqama_status?->value) === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                        @endforeach
                    </select>
                    @error('iqama_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                    <i class="fas fa-save mr-2"></i>Update Iqama
                </button>
            </div>
        </form>
    </div>
</div>
