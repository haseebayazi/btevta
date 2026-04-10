@extends('layouts.app')

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
                        Permission Number
                    </label>
                    <input type="text"
                           name="permission_number"
                           id="permission_number"
                           value="{{ old('permission_number') }}"
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

                <!-- Visa Company License -->
                <div>
                    <label for="visa_company_license" class="block text-gray-700 font-medium mb-2">
                        Visa Company License
                    </label>
                    <input type="text"
                           name="visa_company_license"
                           id="visa_company_license"
                           value="{{ old('visa_company_license') }}"
                           placeholder="License number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Visa company license/registration number</p>
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
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-gray-700 font-medium mb-2">
                        City
                    </label>
                    <input type="text"
                           name="city"
                           id="city"
                           value="{{ old('city') }}"
                           placeholder="e.g., Riyadh, Dubai, Doha"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                </div>

                <!-- Trade (text) -->
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
                </div>

                <!-- Trade (linked) -->
                <div>
                    <label for="trade_id" class="block text-gray-700 font-medium mb-2">
                        Linked Trade
                    </label>
                    <select name="trade_id"
                            id="trade_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Trade --</option>
                        @foreach($trades as $tradeOption)
                            <option value="{{ $tradeOption->id }}"
                                    {{ old('trade_id') == $tradeOption->id ? 'selected' : '' }}>
                                {{ $tradeOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Link to a registered trade for filtering</p>
                </div>

                <!-- Company Size -->
                <div>
                    <label for="company_size" class="block text-gray-700 font-medium mb-2">
                        Company Size
                    </label>
                    <select name="company_size"
                            id="company_size"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Size --</option>
                        @foreach(\App\Enums\EmployerSize::cases() as $size)
                            <option value="{{ $size->value }}"
                                    {{ old('company_size') === $size->value ? 'selected' : '' }}>
                                {{ $size->label() }}
                            </option>
                        @endforeach
                    </select>
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
                </div>
            </div>
        </div>

        <!-- Permission Details Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Permission Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="permission_issue_date" class="block text-gray-700 font-medium mb-2">
                        Permission Issue Date
                    </label>
                    <input type="date"
                           name="permission_issue_date"
                           id="permission_issue_date"
                           value="{{ old('permission_issue_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="permission_expiry_date" class="block text-gray-700 font-medium mb-2">
                        Permission Expiry Date
                    </label>
                    <input type="date"
                           name="permission_expiry_date"
                           id="permission_expiry_date"
                           value="{{ old('permission_expiry_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="permission_document" class="block text-gray-700 font-medium mb-2">
                        Permission Document
                    </label>
                    <input type="file"
                           name="permission_document"
                           id="permission_document"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Upload permission document (PDF, JPG, PNG, max 5MB)</p>
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
                </div>
            </div>

            <!-- Benefits Checkboxes -->
            <div class="mt-6">
                <label class="block text-gray-700 font-medium mb-3">Benefits Provided by Company</label>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="food_by_company" id="food_by_company" value="1"
                               {{ old('food_by_company') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="food_by_company" class="text-gray-700">Food provided by company</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="accommodation_by_company" id="accommodation_by_company" value="1"
                               {{ old('accommodation_by_company') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="accommodation_by_company" class="text-gray-700">Accommodation provided by company</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="transport_by_company" id="transport_by_company" value="1"
                               {{ old('transport_by_company') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="transport_by_company" class="text-gray-700">Transport provided by company</label>
                    </div>
                </div>
            </div>

            <!-- Other Conditions -->
            <div class="mt-6">
                <label for="other_conditions" class="block text-gray-700 font-medium mb-2">
                    Other Conditions & Terms
                </label>
                <textarea name="other_conditions" id="other_conditions" rows="4"
                          placeholder="Additional benefits, overtime policy, leave entitlement, working hours, etc."
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('other_conditions') }}</textarea>
            </div>
        </div>

        <!-- Default Employment Package Breakdown -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Default Employment Package Breakdown</h2>
            <p class="text-sm text-gray-500 mb-4">Define the detailed salary breakdown. This will be the default package for candidates assigned to this employer.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="package_base_salary" class="block text-gray-700 font-medium mb-2">Base Salary</label>
                    <input type="number" name="package_base_salary" id="package_base_salary"
                           value="{{ old('package_base_salary') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_currency" class="block text-gray-700 font-medium mb-2">Package Currency</label>
                    <select name="package_currency" id="package_currency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="SAR" {{ old('package_currency', 'SAR') === 'SAR' ? 'selected' : '' }}>SAR</option>
                        <option value="AED" {{ old('package_currency') === 'AED' ? 'selected' : '' }}>AED</option>
                        <option value="OMR" {{ old('package_currency') === 'OMR' ? 'selected' : '' }}>OMR</option>
                        <option value="QAR" {{ old('package_currency') === 'QAR' ? 'selected' : '' }}>QAR</option>
                        <option value="BHD" {{ old('package_currency') === 'BHD' ? 'selected' : '' }}>BHD</option>
                        <option value="KWD" {{ old('package_currency') === 'KWD' ? 'selected' : '' }}>KWD</option>
                        <option value="USD" {{ old('package_currency') === 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="PKR" {{ old('package_currency') === 'PKR' ? 'selected' : '' }}>PKR</option>
                    </select>
                </div>
                <div>
                    <label for="package_housing_allowance" class="block text-gray-700 font-medium mb-2">Housing Allowance</label>
                    <input type="number" name="package_housing_allowance" id="package_housing_allowance"
                           value="{{ old('package_housing_allowance') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_food_allowance" class="block text-gray-700 font-medium mb-2">Food Allowance</label>
                    <input type="number" name="package_food_allowance" id="package_food_allowance"
                           value="{{ old('package_food_allowance') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_transport_allowance" class="block text-gray-700 font-medium mb-2">Transport Allowance</label>
                    <input type="number" name="package_transport_allowance" id="package_transport_allowance"
                           value="{{ old('package_transport_allowance') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_other_allowance" class="block text-gray-700 font-medium mb-2">Other Allowance</label>
                    <input type="number" name="package_other_allowance" id="package_other_allowance"
                           value="{{ old('package_other_allowance') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Additional Notes</h2>
            <textarea name="notes" id="notes" rows="3"
                      placeholder="Internal notes about this employer..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
        </div>

        <!-- Evidence Upload Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Supporting Documentation</h2>
            <div>
                <label for="evidence" class="block text-gray-700 font-medium mb-2">
                    Upload Evidence/Documentation (Optional)
                </label>
                <input type="file" name="evidence" id="evidence"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    Upload demand letter, employment contract, or other supporting documents.
                    Allowed formats: PDF, JPG, PNG (Max 5MB)
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
</div>
@endsection
