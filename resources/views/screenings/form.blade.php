@extends('layouts.admin')

@section('title', 'Initial Screening - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Candidate Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Initial Screening</h1>
                <p class="text-gray-600 mt-1">
                    Candidate: <span class="font-semibold">{{ $candidate->name }}</span>
                </p>
                <p class="text-sm text-gray-500">
                    BTEVTA ID: {{ $candidate->btevta_id }} | CNIC: {{ $candidate->formatted_cnic }}
                </p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $candidate->status_badge_class }}">
                    {{ $candidate->status_label }}
                </span>
            </div>
        </div>
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

    <!-- Screening Form -->
    <form action="{{ route('screenings.store', $candidate) }}"
          method="POST"
          enctype="multipart/form-data"
          class="bg-white rounded-lg shadow-md p-6">
        @csrf

        <!-- Consent for Work Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Consent for Work Verification</h2>

            <div class="flex items-start gap-3">
                <input type="checkbox"
                       name="consent_for_work"
                       id="consent_for_work"
                       value="1"
                       {{ old('consent_for_work', $screening->consent_for_work ?? false) ? 'checked' : '' }}
                       required
                       class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="consent_for_work" class="text-gray-700">
                    <span class="font-medium">I have verified the candidate's consent for work verification</span>
                    <p class="text-sm text-gray-500 mt-1">
                        The candidate has provided informed consent for background verification and employment screening.
                        This is mandatory to proceed with registration.
                    </p>
                </label>
            </div>
        </div>

        <!-- Placement Interest Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Area of Interest</h2>

            <div class="space-y-3">
                @foreach(\App\Enums\PlacementInterest::cases() as $interest)
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio"
                               name="placement_interest"
                               value="{{ $interest->value }}"
                               {{ old('placement_interest', $screening->placement_interest ?? '') === $interest->value ? 'checked' : '' }}
                               required
                               onchange="toggleCountrySelect(this.value)"
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <div>
                            <span class="font-medium text-gray-700">{{ $interest->label() }}</span>
                            <p class="text-sm text-gray-500">{{ $interest->description() }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Target Country Section (Conditional) -->
        <div id="countrySection"
             class="mb-6 pb-6 border-b {{ old('placement_interest', $screening->placement_interest ?? '') === 'international' ? '' : 'hidden' }}">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Target Country</h2>

            <div>
                <label for="target_country_id" class="block text-gray-700 font-medium mb-2">
                    Select Destination Country <span class="text-red-500">*</span>
                </label>
                <select name="target_country_id"
                        id="target_country_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Country --</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                                {{ old('target_country_id', $screening->target_country_id ?? '') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    Select the country where the candidate is interested in working
                </p>
            </div>
        </div>

        <!-- Screening Decision Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Screening Decision</h2>

            <div class="space-y-3">
                @foreach(\App\Enums\ScreeningStatus::cases() as $status)
                    <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 transition-colors hover:bg-gray-50
                                  {{ $status->value === 'screened' ? 'border-green-300' : ($status->value === 'deferred' ? 'border-yellow-300' : 'border-gray-300') }}">
                        <input type="radio"
                               name="screening_status"
                               value="{{ $status->value }}"
                               {{ old('screening_status', $screening->screening_status ?? '') === $status->value ? 'checked' : '' }}
                               required
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-700">{{ $status->label() }}</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $status->badgeClass() }}">
                                    {{ ucfirst($status->value) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">{{ $status->description() }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Screening Notes Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Screening Notes</h2>

            <div>
                <label for="notes" class="block text-gray-700 font-medium mb-2">
                    Reviewer Notes
                </label>
                <textarea name="notes"
                          id="notes"
                          rows="5"
                          placeholder="Enter any observations, concerns, or recommendations from the screening interview..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes', $screening->notes ?? '') }}</textarea>
                <p class="text-sm text-gray-500 mt-1">
                    Record any relevant information about the candidate's interview performance, communication skills, or other observations
                </p>
            </div>
        </div>

        <!-- Evidence Upload Section (Optional) -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Supporting Evidence (Optional)</h2>

            <div>
                <label for="evidence_file" class="block text-gray-700 font-medium mb-2">
                    Upload Evidence/Documentation
                </label>
                <input type="file"
                       name="evidence_file"
                       id="evidence_file"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-sm text-gray-500 mt-1">
                    Optional: Upload any supporting documents from the screening (e.g., signed consent forms, interview notes)
                    <br>Allowed: PDF, JPG, PNG (Max 5MB)
                </p>

                @if(isset($screening) && $screening->evidence_path)
                    <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"/>
                        </svg>
                        <span>Current file: {{ $screening->evidence_filename }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <a href="{{ route('candidates.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <div class="flex gap-3">
                <button type="submit"
                        name="action"
                        value="save_draft"
                        class="px-6 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                    Save as Pending
                </button>
                <button type="submit"
                        name="action"
                        value="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Submit Screening
                </button>
            </div>
        </div>
    </form>

    <!-- Screening History (if exists) -->
    @if(isset($screening) && $screening->reviewed_at)
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Screening History</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="font-medium text-gray-700">Reviewed By</dt>
                    <dd class="text-gray-600 mt-1">{{ $screening->reviewer->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Reviewed At</dt>
                    <dd class="text-gray-600 mt-1">{{ $screening->reviewed_at->format('d M Y, h:i A') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Previous Status</dt>
                    <dd class="text-gray-600 mt-1">
                        <span class="px-2 py-1 rounded-full text-xs {{ $screening->status->badgeClass() }}">
                            {{ $screening->status->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Placement Interest</dt>
                    <dd class="text-gray-600 mt-1">{{ ucfirst($screening->placement_interest ?? 'Not specified') }}</dd>
                </div>
            </dl>
        </div>
    @endif
</div>

@push('scripts')
<script>
function toggleCountrySelect(placementInterest) {
    const countrySection = document.getElementById('countrySection');
    const countrySelect = document.getElementById('target_country_id');

    if (placementInterest === 'international') {
        countrySection.classList.remove('hidden');
        countrySelect.required = true;
    } else {
        countrySection.classList.add('hidden');
        countrySelect.required = false;
        countrySelect.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const selectedInterest = document.querySelector('input[name="placement_interest"]:checked');
    if (selectedInterest) {
        toggleCountrySelect(selectedInterest.value);
    }
});
</script>
@endpush
@endsection
