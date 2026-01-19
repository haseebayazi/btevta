@extends('layouts.admin')

@section('title', 'Add New Employer')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Add New Employer</h1>
                <p class="text-gray-600 mt-1">Create a new employer record with employment package details</p>
            </div>
            <a href="{{ route('admin.employers.index') }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to List
            </a>
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
    <form action="{{ route('admin.employers.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

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
                           value="{{ old('permission_number') }}"
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
                           value="{{ old('visa_issuing_company') }}"
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
                                    {{ old('country_id') == $country->id ? 'selected' : '' }}>
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
                           value="{{ old('sector') }}"
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
                           value="{{ old('trade') }}"
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
                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
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
                           value="{{ old('basic_salary') }}"
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
                        <option value="PKR" {{ old('salary_currency', 'PKR') === 'PKR' ? 'selected' : '' }}>PKR - Pakistani Rupee</option>
                        <option value="SAR" {{ old('salary_currency') === 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal</option>
                        <option value="AED" {{ old('salary_currency') === 'AED' ? 'selected' : '' }}>AED - UAE Dirham</option>
                        <option value="OMR" {{ old('salary_currency') === 'OMR' ? 'selected' : '' }}>OMR - Omani Rial</option>
                        <option value="QAR" {{ old('salary_currency') === 'QAR' ? 'selected' : '' }}>QAR - Qatari Riyal</option>
                        <option value="BHD" {{ old('salary_currency') === 'BHD' ? 'selected' : '' }}>BHD - Bahraini Dinar</option>
                        <option value="KWD" {{ old('salary_currency') === 'KWD' ? 'selected' : '' }}>KWD - Kuwaiti Dinar</option>
                        <option value="USD" {{ old('salary_currency') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
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
                               {{ old('food_by_company') ? 'checked' : '' }}
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
                               {{ old('accommodation_by_company') ? 'checked' : '' }}
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
                               {{ old('transport_by_company') ? 'checked' : '' }}
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
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('other_conditions') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Describe any additional employment terms and conditions</p>
            </div>
        </div>

        <!-- Evidence Upload Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Supporting Documentation</h2>

            <div>
                <label for="evidence_file" class="block text-gray-700 font-medium mb-2">
                    Upload Evidence/Documentation (Optional)
                </label>
                <input type="file"
                       name="evidence_file"
                       id="evidence_file"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    Upload demand letter, employment contract, or other supporting documents
                    <br>Allowed formats: PDF, JPG, PNG (Max 10MB)
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('admin.employers.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Save Employer
            </button>
        </div>
    </form>

    <!-- Information Panel -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Employer Information Guidelines</h3>
        <ul class="list-disc list-inside space-y-2 text-gray-700 text-sm">
            <li><strong>Permission Number:</strong> Must be obtained after demand letter approval</li>
            <li><strong>Visa Issuing Company:</strong> The actual company/organization issuing employment visas</li>
            <li><strong>Employment Package:</strong> Include all financial and non-financial benefits</li>
            <li><strong>Benefits:</strong> Check all benefits provided by the employer (food, accommodation, transport)</li>
            <li><strong>Evidence:</strong> Upload demand letters, contracts, or approval documents for record-keeping</li>
            <li>Employer records can be linked to multiple candidates during the registration process</li>
        </ul>
    </div>
</div>
@endsection
