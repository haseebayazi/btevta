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
                <div>
                    <label for="permission_number" class="block text-gray-700 font-medium mb-2">Permission Number</label>
                    <input type="text" name="permission_number" id="permission_number"
                           value="{{ old('permission_number', $employer->permission_number) }}"
                           placeholder="After approved demand letter"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="visa_issuing_company" class="block text-gray-700 font-medium mb-2">
                        Visa Issuing Company <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="visa_issuing_company" id="visa_issuing_company"
                           value="{{ old('visa_issuing_company', $employer->visa_issuing_company) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="visa_company_license" class="block text-gray-700 font-medium mb-2">Visa Company License</label>
                    <input type="text" name="visa_company_license" id="visa_company_license"
                           value="{{ old('visa_company_license', $employer->visa_company_license) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="country_id" class="block text-gray-700 font-medium mb-2">
                        Country <span class="text-red-500">*</span>
                    </label>
                    <select name="country_id" id="country_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Country --</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                    {{ old('country_id', $employer->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->flag_emoji ? $country->flag_emoji . ' ' : '' }}{{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="city" class="block text-gray-700 font-medium mb-2">City</label>
                    <input type="text" name="city" id="city"
                           value="{{ old('city', $employer->city) }}"
                           placeholder="e.g., Riyadh, Dubai, Doha"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="sector" class="block text-gray-700 font-medium mb-2">Sector</label>
                    <input type="text" name="sector" id="sector"
                           value="{{ old('sector', $employer->sector) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="trade" class="block text-gray-700 font-medium mb-2">Trade/Occupation</label>
                    <input type="text" name="trade" id="trade"
                           value="{{ old('trade', $employer->trade) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="trade_id" class="block text-gray-700 font-medium mb-2">Linked Trade</label>
                    <select name="trade_id" id="trade_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Trade --</option>
                        @foreach($trades as $tradeOption)
                            <option value="{{ $tradeOption->id }}"
                                    {{ old('trade_id', $employer->trade_id) == $tradeOption->id ? 'selected' : '' }}>
                                {{ $tradeOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="company_size" class="block text-gray-700 font-medium mb-2">Company Size</label>
                    <select name="company_size" id="company_size"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Size --</option>
                        @foreach(\App\Enums\EmployerSize::cases() as $size)
                            <option value="{{ $size->value }}"
                                    {{ old('company_size', $employer->company_size?->value) === $size->value ? 'selected' : '' }}>
                                {{ $size->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="is_active" class="block text-gray-700 font-medium mb-2">Status</label>
                    <select name="is_active" id="is_active"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1" {{ old('is_active', $employer->is_active) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $employer->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Permission Details Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Permission Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="permission_issue_date" class="block text-gray-700 font-medium mb-2">Permission Issue Date</label>
                    <input type="date" name="permission_issue_date" id="permission_issue_date"
                           value="{{ old('permission_issue_date', $employer->permission_issue_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="permission_expiry_date" class="block text-gray-700 font-medium mb-2">Permission Expiry Date</label>
                    <input type="date" name="permission_expiry_date" id="permission_expiry_date"
                           value="{{ old('permission_expiry_date', $employer->permission_expiry_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @if($employer->permission_expiring)
                        <p class="text-xs text-yellow-600 mt-1"><i class="fas fa-exclamation-triangle"></i> Permission expiring soon!</p>
                    @endif
                    @if($employer->permission_expired)
                        <p class="text-xs text-red-600 mt-1"><i class="fas fa-times-circle"></i> Permission has expired!</p>
                    @endif
                </div>

                <div>
                    <label for="permission_document" class="block text-gray-700 font-medium mb-2">Permission Document</label>
                    @if($employer->permission_document_path)
                        <p class="text-sm text-blue-600 mb-2">Current: {{ basename($employer->permission_document_path) }}</p>
                    @endif
                    <input type="file" name="permission_document" id="permission_document"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (Max 5MB)</p>
                </div>
            </div>
        </div>

        <!-- Employment Package Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Employment Package</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="basic_salary" class="block text-gray-700 font-medium mb-2">Basic Salary</label>
                    <input type="number" name="basic_salary" id="basic_salary"
                           value="{{ old('basic_salary', $employer->basic_salary) }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="salary_currency" class="block text-gray-700 font-medium mb-2">Currency</label>
                    <select name="salary_currency" id="salary_currency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['PKR' => 'PKR - Pakistani Rupee', 'SAR' => 'SAR - Saudi Riyal', 'AED' => 'AED - UAE Dirham', 'OMR' => 'OMR - Omani Rial', 'QAR' => 'QAR - Qatari Riyal', 'BHD' => 'BHD - Bahraini Dinar', 'KWD' => 'KWD - Kuwaiti Dinar', 'USD' => 'USD - US Dollar'] as $code => $label)
                            <option value="{{ $code }}" {{ old('salary_currency', $employer->salary_currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-gray-700 font-medium mb-3">Benefits Provided by Company</label>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="food_by_company" id="food_by_company" value="1"
                               {{ old('food_by_company', $employer->food_by_company) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="food_by_company" class="text-gray-700">Food provided by company</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="accommodation_by_company" id="accommodation_by_company" value="1"
                               {{ old('accommodation_by_company', $employer->accommodation_by_company) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="accommodation_by_company" class="text-gray-700">Accommodation provided by company</label>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="transport_by_company" id="transport_by_company" value="1"
                               {{ old('transport_by_company', $employer->transport_by_company) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="transport_by_company" class="text-gray-700">Transport provided by company</label>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label for="other_conditions" class="block text-gray-700 font-medium mb-2">Other Conditions & Terms</label>
                <textarea name="other_conditions" id="other_conditions" rows="4"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('other_conditions', $employer->other_conditions) }}</textarea>
            </div>
        </div>

        <!-- Default Employment Package Breakdown -->
        @php $pkg = $employer->default_package_object; @endphp
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Default Employment Package Breakdown</h2>
            <p class="text-sm text-gray-500 mb-4">Detailed salary breakdown for candidates assigned to this employer.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="package_base_salary" class="block text-gray-700 font-medium mb-2">Base Salary</label>
                    <input type="number" name="package_base_salary" id="package_base_salary"
                           value="{{ old('package_base_salary', $pkg->baseSalary ?: '') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_currency" class="block text-gray-700 font-medium mb-2">Package Currency</label>
                    <select name="package_currency" id="package_currency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['SAR', 'AED', 'OMR', 'QAR', 'BHD', 'KWD', 'USD', 'PKR'] as $c)
                            <option value="{{ $c }}" {{ old('package_currency', $pkg->currency) === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="package_housing_allowance" class="block text-gray-700 font-medium mb-2">Housing Allowance</label>
                    <input type="number" name="package_housing_allowance" id="package_housing_allowance"
                           value="{{ old('package_housing_allowance', $pkg->housingAllowance ?: '') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_food_allowance" class="block text-gray-700 font-medium mb-2">Food Allowance</label>
                    <input type="number" name="package_food_allowance" id="package_food_allowance"
                           value="{{ old('package_food_allowance', $pkg->foodAllowance ?: '') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_transport_allowance" class="block text-gray-700 font-medium mb-2">Transport Allowance</label>
                    <input type="number" name="package_transport_allowance" id="package_transport_allowance"
                           value="{{ old('package_transport_allowance', $pkg->transportAllowance ?: '') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="package_other_allowance" class="block text-gray-700 font-medium mb-2">Other Allowance</label>
                    <input type="number" name="package_other_allowance" id="package_other_allowance"
                           value="{{ old('package_other_allowance', $pkg->otherAllowance ?: '') }}" step="0.01" min="0" placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Additional Notes</h2>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes', $employer->notes) }}</textarea>
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
                <label for="evidence" class="block text-gray-700 font-medium mb-2">
                    Upload New Evidence/Documentation (Optional)
                </label>
                <input type="file" name="evidence" id="evidence"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    Allowed formats: PDF, JPG, PNG (Max 5MB)
                    @if($employer->evidence_path)
                        <br><span class="text-orange-600">Uploading a new file will replace the existing document</span>
                    @endif
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
                Update Employer
            </button>
        </div>
    </form>

    <!-- Audit Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Record Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div><span class="font-medium">Created:</span> {{ $employer->created_at->format('M d, Y h:i A') }}</div>
            <div><span class="font-medium">Last Updated:</span> {{ $employer->updated_at->format('M d, Y h:i A') }}</div>
            @if($employer->creator)
            <div><span class="font-medium">Created By:</span> {{ $employer->creator->name }}</div>
            @endif
            @if($employer->verified)
            <div>
                <span class="font-medium">Verified:</span>
                {{ $employer->verified_at?->format('M d, Y h:i A') }}
                @if($employer->verifiedByUser) by {{ $employer->verifiedByUser->name }} @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
