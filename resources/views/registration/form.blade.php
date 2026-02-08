@extends('layouts.admin')

@section('title', 'Candidate Registration - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Candidate Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Candidate Registration</h1>
                <p class="text-gray-600 mt-1">
                    <span class="font-semibold">{{ $candidate->name }}</span> - {{ $candidate->btevta_id }}
                </p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $candidate->status_badge_class }}">
                    {{ $candidate->status_label }}
                </span>
            </div>
        </div>

        <!-- Screening Status Check -->
        @if($screeningCheck && !$screeningCheck['can_proceed'])
            <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <strong class="font-bold">Registration Not Allowed</strong>
                        <p>{{ $screeningCheck['reason'] }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Please correct the following errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Registration Form -->
    <form action="{{ route('registration.store', $candidate) }}"
          method="POST"
          class="space-y-6">
        @csrf

        <!-- Allocation Section (NEW in WASL v3) -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-800">Allocation Details</h2>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">Required</span>
            </div>

            <p class="text-sm text-gray-600 mb-6">
                Allocate the candidate to a campus, program, implementing partner, and trade.
                A batch will be automatically created based on these selections.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Campus Selection -->
                <div>
                    <label for="campus_id" class="block text-gray-700 font-medium mb-2">
                        Campus <span class="text-red-500">*</span>
                    </label>
                    <select name="campus_id"
                            id="campus_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Campus --</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}"
                                    {{ old('campus_id', $candidate->campus_id) == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }} ({{ $campus->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Program Selection -->
                <div>
                    <label for="program_id" class="block text-gray-700 font-medium mb-2">
                        Program <span class="text-red-500">*</span>
                    </label>
                    <select name="program_id"
                            id="program_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Program --</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}"
                                    data-duration="{{ $program->duration_weeks }}"
                                    {{ old('program_id', $candidate->program_id) == $program->id ? 'selected' : '' }}>
                                {{ $program->name }} ({{ $program->duration_weeks }} weeks)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select the training program</p>
                </div>

                <!-- Trade Selection -->
                <div>
                    <label for="trade_id" class="block text-gray-700 font-medium mb-2">
                        Trade <span class="text-red-500">*</span>
                    </label>
                    <select name="trade_id"
                            id="trade_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Trade --</option>
                        @foreach($trades as $trade)
                            <option value="{{ $trade->id }}"
                                    {{ old('trade_id', $candidate->trade_id) == $trade->id ? 'selected' : '' }}>
                                {{ $trade->name }} ({{ $trade->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select the specific trade/skill</p>
                </div>

                <!-- Implementing Partner Selection -->
                <div>
                    <label for="implementing_partner_id" class="block text-gray-700 font-medium mb-2">
                        Implementing Partner (Optional)
                    </label>
                    <select name="implementing_partner_id"
                            id="implementing_partner_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- None --</option>
                        @foreach($implementingPartners as $partner)
                            <option value="{{ $partner->id }}"
                                    {{ old('implementing_partner_id', $candidate->implementing_partner_id) == $partner->id ? 'selected' : '' }}>
                                {{ $partner->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Optional: Partner organization facilitating training</p>
                </div>
            </div>

            <!-- Auto-Batch Information -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-blue-900">Auto-Batch Assignment</h3>
                        <p class="text-sm text-blue-800 mt-1">
                            Upon registration, the candidate will be automatically assigned to a batch based on
                            Campus + Program + Trade combination. If no suitable batch exists, a new batch will be created.
                        </p>
                        <p class="text-xs text-blue-700 mt-2">
                            <strong>Current Batch Size:</strong> {{ config('wasl.batch_size', 25) }} candidates per batch
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next of Kin Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Next of Kin Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nok_name" class="block text-gray-700 font-medium mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="nok_name"
                           id="nok_name"
                           value="{{ old('nok_name', $candidate->nextOfKin?->name ?? '') }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="nok_relationship" class="block text-gray-700 font-medium mb-2">
                        Relationship <span class="text-red-500">*</span>
                    </label>
                    <select name="nok_relationship"
                            id="nok_relationship"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select --</option>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="spouse">Spouse</option>
                        <option value="sibling">Sibling</option>
                        <option value="guardian">Guardian</option>
                    </select>
                </div>

                <div>
                    <label for="nok_cnic" class="block text-gray-700 font-medium mb-2">
                        CNIC <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="nok_cnic"
                           id="nok_cnic"
                           value="{{ old('nok_cnic', $candidate->nextOfKin?->cnic ?? '') }}"
                           placeholder="00000-0000000-0"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="nok_phone" class="block text-gray-700 font-medium mb-2">
                        Contact Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="nok_phone"
                           id="nok_phone"
                           value="{{ old('nok_phone', $candidate->nextOfKin?->phone ?? '') }}"
                           placeholder="03XX-XXXXXXX"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label for="nok_address" class="block text-gray-700 font-medium mb-2">
                        Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="nok_address"
                              id="nok_address"
                              rows="2"
                              required
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('nok_address', $candidate->nextOfKin?->address ?? '') }}</textarea>
                </div>

                <!-- Financial Account Details (NEW in WASL v3) -->
                <div>
                    <label for="nok_bank_name" class="block text-gray-700 font-medium mb-2">
                        Bank Name (Optional)
                    </label>
                    <input type="text"
                           name="nok_bank_name"
                           id="nok_bank_name"
                           value="{{ old('nok_bank_name', $candidate->nextOfKin?->bank_name ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="nok_account_number" class="block text-gray-700 font-medium mb-2">
                        Account Number (Optional)
                    </label>
                    <input type="text"
                           name="nok_account_number"
                           id="nok_account_number"
                           value="{{ old('nok_account_number', $candidate->nextOfKin?->account_number ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('candidates.show', $candidate) }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    {{ (!$screeningCheck || !$screeningCheck['can_proceed']) ? 'disabled' : '' }}
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                Complete Registration & Create Batch
            </button>
        </div>
    </form>

    <!-- Registration Info Panel -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">What Happens Next?</h3>
        <ol class="list-decimal list-inside space-y-2 text-gray-700">
            <li>Candidate will be allocated to the selected Campus, Program, and Trade</li>
            <li>System will find an existing batch or create a new one automatically</li>
            <li>A unique batch number and allocated number will be generated</li>
            <li>Candidate status will be updated to "Registered"</li>
            <li>Candidate can then proceed to the Training phase</li>
        </ol>
    </div>
</div>

@push('scripts')
<script>
// Form validation and dynamic fields
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus_id');
    const programSelect = document.getElementById('program_id');
    const tradeSelect = document.getElementById('trade_id');

    // Update expected batch number preview (optional enhancement)
    function updateBatchPreview() {
        const campus = campusSelect.options[campusSelect.selectedIndex];
        const program = programSelect.options[programSelect.selectedIndex];
        const trade = tradeSelect.options[tradeSelect.selectedIndex];

        if (campus.value && program.value && trade.value) {
            // This could show a preview of the batch number format
            console.log('Batch will be created for:', campus.text, program.text, trade.text);
        }
    }

    campusSelect.addEventListener('change', updateBatchPreview);
    programSelect.addEventListener('change', updateBatchPreview);
    tradeSelect.addEventListener('change', updateBatchPreview);
});
</script>
@endpush
@endsection
