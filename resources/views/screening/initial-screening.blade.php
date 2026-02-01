@extends('layouts.app')

@section('title', 'Initial Screening - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('screening.initial-dashboard') }}" class="hover:text-blue-600">Initial Screening</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">Screen Candidate</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Initial Screening</h1>
                <p class="text-gray-600 mt-1">Conduct initial screening for {{ $candidate->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('candidates.show', $candidate) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Candidate
                </a>
            </div>
        </div>
    </div>

    <!-- Candidate Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Candidate Name -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Candidate</p>
                    <p class="font-semibold text-gray-900">{{ $candidate->name }}</p>
                </div>
            </div>
        </div>

        <!-- TheLeap ID -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-id-card text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">TheLeap ID</p>
                    <p class="font-semibold text-gray-900">{{ $candidate->btevta_id ?? 'Not Assigned' }}</p>
                </div>
            </div>
        </div>

        <!-- Campus -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Campus</p>
                    <p class="font-semibold text-gray-900">{{ $candidate->campus->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Trade -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-briefcase text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Trade</p>
                    <p class="font-semibold text-gray-900">{{ $candidate->trade->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Screening Info (if any) -->
    @if($existingScreening)
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Existing Screening Found</h3>
                <p class="mt-1 text-sm text-blue-700">
                    This candidate was previously screened on {{ $existingScreening->reviewed_at?->format('M d, Y') ?? 'N/A' }} 
                    with outcome: <strong>{{ ucfirst($existingScreening->screening_status ?? 'N/A') }}</strong>
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Screening Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-blue-50">
            <h2 class="text-xl font-semibold text-gray-900">Screening Form</h2>
            <p class="text-sm text-gray-600 mt-1">Complete all fields to submit screening result</p>
        </div>

        <form action="{{ route('candidates.initial-screening.store', $candidate) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">

            <!-- Consent for Work -->
            <div class="mb-6">
                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" 
                           name="consent_for_work" 
                           value="1" 
                           {{ old('consent_for_work') ? 'checked' : '' }}
                           class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                           required>
                    <div class="flex-1">
                        <span class="font-semibold text-gray-900">Consent for Work <span class="text-red-500">*</span></span>
                        <p class="text-sm text-gray-600 mt-1">
                            I confirm that the candidate has provided informed consent to work and understands the terms and conditions of overseas employment.
                            This is a legal requirement and must be verified before proceeding.
                        </p>
                    </div>
                </label>
                @error('consent_for_work')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Placement Interest -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-900 mb-3">
                    Placement Interest <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition {{ old('placement_interest') === 'local' ? 'border-blue-500 bg-blue-50' : '' }}">
                        <input type="radio" 
                               name="placement_interest" 
                               value="local" 
                               {{ old('placement_interest') === 'local' ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                               onchange="toggleCountryDropdown()"
                               required>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Local Placement</span>
                            <span class="block text-xs text-gray-500 mt-1">Seeking employment within Pakistan</span>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition {{ old('placement_interest') === 'international' ? 'border-blue-500 bg-blue-50' : '' }}">
                        <input type="radio" 
                               name="placement_interest" 
                               value="international" 
                               {{ old('placement_interest') === 'international' ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                               onchange="toggleCountryDropdown()"
                               required>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">International Placement</span>
                            <span class="block text-xs text-gray-500 mt-1">Seeking overseas employment</span>
                        </div>
                    </label>
                </div>
                @error('placement_interest')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Target Country (shown only if International selected) -->
            <div id="country-field" class="mb-6 {{ old('placement_interest') === 'international' ? '' : 'hidden' }}">
                <label for="target_country_id" class="block text-sm font-semibold text-gray-900 mb-2">
                    Target Country <span class="text-red-500">*</span>
                </label>
                <select name="target_country_id" 
                        id="target_country_id" 
                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select Destination Country --</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" {{ old('target_country_id') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
                @error('target_country_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">
                    Screening Notes
                </label>
                <textarea name="notes" 
                          id="notes" 
                          rows="4" 
                          class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Add any relevant notes about this screening...">{{ old('notes') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Optional: Any observations or comments about the screening</p>
                @error('notes')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Evidence Upload -->
            <div class="mb-6">
                <label for="evidence" class="block text-sm font-semibold text-gray-900 mb-2">
                    Evidence Document
                </label>
                <input type="file" 
                       name="evidence" 
                       id="evidence" 
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-500">Optional: Upload supporting documentation (PDF, JPG, PNG - Max 10MB)</p>
                @error('evidence')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Screening Outcome -->
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-900 mb-3">
                    Screening Outcome <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-500 transition">
                        <input type="radio" 
                               name="screening_status" 
                               value="screened" 
                               {{ old('screening_status') === 'screened' ? 'checked' : '' }}
                               class="h-4 w-4 text-green-600 focus:ring-green-500"
                               required>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">✓ Screened</span>
                            <span class="block text-xs text-gray-500 mt-1">Candidate approved for registration</span>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-yellow-500 transition">
                        <input type="radio" 
                               name="screening_status" 
                               value="pending" 
                               {{ old('screening_status') === 'pending' ? 'checked' : '' }}
                               class="h-4 w-4 text-yellow-600 focus:ring-yellow-500"
                               required>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">⏳ Pending</span>
                            <span class="block text-xs text-gray-500 mt-1">Save for later review</span>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-red-500 transition">
                        <input type="radio" 
                               name="screening_status" 
                               value="deferred" 
                               {{ old('screening_status') === 'deferred' ? 'checked' : '' }}
                               class="h-4 w-4 text-red-600 focus:ring-red-500"
                               required>
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">✗ Deferred</span>
                            <span class="block text-xs text-gray-500 mt-1">Not suitable at this time</span>
                        </div>
                    </label>
                </div>
                @error('screening_status')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="{{ route('candidates.show', $candidate) }}" 
                   class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center">
                    <i class="fas fa-save mr-2"></i>Submit Screening
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCountryDropdown() {
    const placementInterest = document.querySelector('input[name="placement_interest"]:checked');
    const countryField = document.getElementById('country-field');
    const countrySelect = document.getElementById('target_country_id');
    
    if (placementInterest && placementInterest.value === 'international') {
        countryField.classList.remove('hidden');
        countrySelect.required = true;
    } else {
        countryField.classList.add('hidden');
        countrySelect.required = false;
        countrySelect.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCountryDropdown();
});
</script>
@endsection
