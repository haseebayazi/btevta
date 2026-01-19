@extends('layouts.admin')

@section('title', 'Edit Employer')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Employer</h1>
                <p class="text-gray-600 mt-1">Update employer record and employment package details</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.employers.show', $employer) }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                    View Details
                </a>
                <a href="{{ route('admin.employers.index') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                    Back to List
                </a>
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

    <!-- Employer Form -->
    <form action="{{ route('admin.employers.update', $employer) }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Basic Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Permission Number -->
                <div>
                    <label for="permission_number" class="block text-gray-700 font-medium mb-2">
                        Permission Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="permission_number"
                           id="permission_number"
                           value="{{ old('permission_number', $employer->permission_number) }}"
                           required
                           placeholder="After approved demand letter"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Official permission number from approved demand letter</p>
                </div>

                <!-- Visa Issuing Company -->
                <div>
                    <label for="visa_issuing_company" class="block text-gray-700 font-medium mb-2">
                        Visa Issuing Company <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="visa_issuing_company"
                           id="visa_issuing_company"
                           value="{{ old('visa_issuing_company', $employer->visa_issuing_company) }}"
                           required
                           placeholder="e.g., ARAMCO, Saudi Electric Company"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Name of the company issuing employment visas</p>
                </div>

                <!-- Country -->
                <div>
                    <label for="country_id" class="block text-gray-700 font-medium mb-2">
                        Country <span class="text-red-500">*</span>
                    </label>
                    <select name="country_id"
                            id="country_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Country --</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                    {{ old('country_id', $employer->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->flag_emoji ? $country->flag_emoji . ' ' : '' }}{{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Destination country for employment</p>
                </div>

                <!-- Sector -->
                <div>
                    <label for="sector" class="block text-gray-700 font-medium mb-2">
                        Sector
                    </label>
                    <input type="text"
                           name="sector"
                           id="sector"
                           value="{{ old('sector', $employer->sector) }}"
                           placeholder="e.g., Construction, Healthcare, Manufacturing"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Industry sector or business domain</p>
                </div>

                <!-- Trade -->
                <div>
                    <label for="trade" class="block text-gray-700 font-medium mb-2">
                        Trade/Occupation
                    </label>
                    <input type="text"
                           name="trade"
                           id="trade"
                           value="{{ old('trade', $employer->trade) }}"
                           placeholder="e.g., Electrician, Plumber, Welder"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Specific trade or job position</p>
                </div>

                <!-- Active Status -->
                <div>
                    <label for="is_active" class="block text-gray-700 font-medium mb-2">
                        Status
                    </label>
                    <select name="is_active"
                            id="is_active"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1" {{ old('is_active', $employer->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $employer->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Set employer status</p>
                </div>
            </div>
        </div>

        <!-- Employment Package Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Employment Package</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Salary -->
                <div>
                    <label for="basic_salary" class="block text-gray-700 font-medium mb-2">
                        Basic Salary
                    </label>
                    <input type="number"
                           name="basic_salary"
                           id="basic_salary"
                           value="{{ old('basic_salary', $employer->basic_salary) }}"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Monthly basic salary amount</p>
                </div>

                <!-- Salary Currency -->
                <div>
                    <label for="salary_currency" class="block text-gray-700 font-medium mb-2">
                        Currency
                    </label>
                    <select name="salary_currency"
                            id="salary_currency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="PKR" {{ old('salary_currency', $employer->salary_currency) === 'PKR' ? 'selected' : '' }}>PKR - Pakistani Rupee</option>
                        <option value="SAR" {{ old('salary_currency', $employer->salary_currency) === 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal</option>
                        <option value="AED" {{ old('salary_currency', $employer->salary_currency) === 'AED' ? 'selected' : '' }}>AED - UAE Dirham</option>
                        <option value="OMR" {{ old('salary_currency', $employer->salary_currency) === 'OMR' ? 'selected' : '' }}>OMR - Omani Rial</option>
                        <option value="QAR" {{ old('salary_currency', $employer->salary_currency) === 'QAR' ? 'selected' : '' }}>QAR - Qatari Riyal</option>
                        <option value="BHD" {{ old('salary_currency', $employer->salary_currency) === 'BHD' ? 'selected' : '' }}>BHD - Bahraini Dinar</option>
                        <option value="KWD" {{ old('salary_currency', $employer->salary_currency) === 'KWD' ? 'selected' : '' }}>KWD - Kuwaiti Dinar</option>
                        <option value="USD" {{ old('salary_currency', $employer->salary_currency) === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Currency for salary payment</p>
                </div>
            </div>

            <!-- Benefits Checkboxes -->
            <div class="mt-6">
                <label class="block text-gray-700 font-medium mb-3">Benefits Provided by Company</label>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               name="food_by_company"
                               id="food_by_company"
                               value="1"
                               {{ old('food_by_company', $employer->food_by_company) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="food_by_company" class="text-gray-700">
                            Food provided by company
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               name="accommodation_by_company"
                               id="accommodation_by_company"
                               value="1"
                               {{ old('accommodation_by_company', $employer->accommodation_by_company) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="accommodation_by_company" class="text-gray-700">
                            Accommodation provided by company
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               name="transport_by_company"
                               id="transport_by_company"
                               value="1"
                               {{ old('transport_by_company', $employer->transport_by_company) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="transport_by_company" class="text-gray-700">
                            Transport provided by company
                        </label>
                    </div>
                </div>
            </div>

            <!-- Other Conditions -->
            <div class="mt-6">
                <label for="other_conditions" class="block text-gray-700 font-medium mb-2">
                    Other Conditions & Terms
                </label>
                <textarea name="other_conditions"
                          id="other_conditions"
                          rows="4"
                          placeholder="Additional benefits, overtime policy, leave entitlement, working hours, etc."
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('other_conditions', $employer->other_conditions) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Describe any additional employment terms and conditions</p>
            </div>
        </div>

        <!-- Evidence Upload Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Supporting Documentation</h2>

            @if($employer->evidence_path)
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800 mb-2">
                        <strong>Current Document:</strong> {{ basename($employer->evidence_path) }}
                    </p>
                    <a href="{{ route('admin.employers.download-evidence', $employer) }}"
                       class="text-blue-600 hover:text-blue-800 text-sm underline">
                        Download Current Document
                    </a>
                </div>
            @endif

            <div>
                <label for="evidence_file" class="block text-gray-700 font-medium mb-2">
                    Upload New Evidence/Documentation (Optional)
                </label>
                <input type="file"
                       name="evidence_file"
                       id="evidence_file"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    Upload demand letter, employment contract, or other supporting documents
                    <br>Allowed formats: PDF, JPG, PNG (Max 10MB)
                    @if($employer->evidence_path)
                        <br><span class="text-orange-600">Uploading a new file will replace the existing document</span>
                    @endif
                </p>
            </div>
        </div>

        <!-- Linked Candidates (if any) -->
        @if($employer->currentCandidates()->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Linked Candidates</h2>
            <div class="bg-yellow-50 border border-yellow-300 rounded-md p-4">
                <p class="text-sm text-yellow-800">
                    <strong>Note:</strong> This employer is currently linked to {{ $employer->currentCandidates()->count() }} active candidate(s).
                    Changes to employment package details may affect candidate records.
                </p>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('admin.employers.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Update Employer
            </button>
        </div>
    </form>

    <!-- Audit Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Record Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div>
                <span class="font-medium">Created:</span>
                {{ $employer->created_at->format('M d, Y h:i A') }}
            </div>
            <div>
                <span class="font-medium">Last Updated:</span>
                {{ $employer->updated_at->format('M d, Y h:i A') }}
            </div>
            @if($employer->creator)
            <div>
                <span class="font-medium">Created By:</span>
                {{ $employer->creator->name }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
