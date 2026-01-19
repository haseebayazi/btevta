@extends('layouts.admin')

@section('title', 'Post-Departure Tracking')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Post-Departure Tracking</h1>
                <p class="text-gray-600 mt-1">Track residency, identity, and employment details after departure</p>
            </div>
            <a href="{{ route('admin.candidates.show', $candidate) }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to Candidate
            </a>
        </div>
    </div>

    <!-- Candidate Information Panel -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Candidate:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->full_name }}</strong>
            </div>
            <div>
                <span class="text-gray-600">BTEVTA ID:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->btevta_id }}</strong>
            </div>
            <div>
                <span class="text-gray-600">Destination:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->departure->destination ?? 'N/A' }}</strong>
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

    <form action="{{ isset($postDeparture) ? route('admin.post-departure.update', $postDeparture) : route('admin.post-departure.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($postDeparture))
            @method('PUT')
        @else
            <input type="hidden" name="departure_id" value="{{ $departure->id }}">
        @endif

        <!-- Residency & Identity Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
                Residency & Identity Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Residency Number -->
                <div>
                    <label for="residency_number" class="block text-gray-700 font-medium mb-2">
                        Residency Number (Iqama)
                    </label>
                    <input type="text"
                           name="residency_number"
                           id="residency_number"
                           value="{{ old('residency_number', $postDeparture->residency_number ?? '') }}"
                           placeholder="e.g., 2123456789"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Residency/Iqama number in destination country</p>
                </div>

                <!-- Residency Expiry -->
                <div>
                    <label for="residency_expiry" class="block text-gray-700 font-medium mb-2">
                        Residency Expiry Date
                    </label>
                    <input type="date"
                           name="residency_expiry"
                           id="residency_expiry"
                           value="{{ old('residency_expiry', $postDeparture->residency_expiry ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Foreign License Number -->
                <div>
                    <label for="foreign_license_number" class="block text-gray-700 font-medium mb-2">
                        Foreign License Number
                    </label>
                    <input type="text"
                           name="foreign_license_number"
                           id="foreign_license_number"
                           value="{{ old('foreign_license_number', $postDeparture->foreign_license_number ?? '') }}"
                           placeholder="Driving or professional license number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Foreign Mobile Number -->
                <div>
                    <label for="foreign_mobile_number" class="block text-gray-700 font-medium mb-2">
                        Foreign Mobile Number
                    </label>
                    <input type="text"
                           name="foreign_mobile_number"
                           id="foreign_mobile_number"
                           value="{{ old('foreign_mobile_number', $postDeparture->foreign_mobile_number ?? '') }}"
                           placeholder="e.g., +966 5XXXXXXXX"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Mobile number in destination country</p>
                </div>

                <!-- Foreign Bank Name -->
                <div>
                    <label for="foreign_bank_name" class="block text-gray-700 font-medium mb-2">
                        Foreign Bank Name
                    </label>
                    <input type="text"
                           name="foreign_bank_name"
                           id="foreign_bank_name"
                           value="{{ old('foreign_bank_name', $postDeparture->foreign_bank_name ?? '') }}"
                           placeholder="e.g., Al Rajhi Bank, Emirates NBD"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Foreign Bank Account -->
                <div>
                    <label for="foreign_bank_account" class="block text-gray-700 font-medium mb-2">
                        Foreign Bank Account Number
                    </label>
                    <input type="text"
                           name="foreign_bank_account"
                           id="foreign_bank_account"
                           value="{{ old('foreign_bank_account', $postDeparture->foreign_bank_account ?? '') }}"
                           placeholder="Bank account number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Tracking App Registration -->
                <div>
                    <label for="tracking_app_registration" class="block text-gray-700 font-medium mb-2">
                        Tracking App Registration
                    </label>
                    <select name="tracking_app_registration"
                            id="tracking_app_registration"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Status --</option>
                        <option value="registered" {{ old('tracking_app_registration', $postDeparture->tracking_app_registration ?? '') === 'registered' ? 'selected' : '' }}>Registered</option>
                        <option value="pending" {{ old('tracking_app_registration', $postDeparture->tracking_app_registration ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="not_registered" {{ old('tracking_app_registration', $postDeparture->tracking_app_registration ?? '') === 'not_registered' ? 'selected' : '' }}>Not Registered</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Registration status in government tracking app (e.g., Absher, Qiwa)</p>
                </div>
            </div>

            <!-- Document Uploads -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Residency Proof -->
                <div>
                    <label for="residency_proof" class="block text-gray-700 font-medium mb-2">
                        Residency Proof Document
                    </label>
                    @if(isset($postDeparture) && $postDeparture->residency_proof_path)
                        <div class="mb-2 text-sm text-blue-600">
                            Current: <a href="{{ route('admin.post-departure.download-residency', $postDeparture) }}" class="underline">Download</a>
                        </div>
                    @endif
                    <input type="file"
                           name="residency_proof"
                           id="residency_proof"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Upload residency/Iqama document (PDF, JPG, PNG)</p>
                </div>

                <!-- Foreign License -->
                <div>
                    <label for="foreign_license" class="block text-gray-700 font-medium mb-2">
                        Foreign License Document
                    </label>
                    @if(isset($postDeparture) && $postDeparture->foreign_license_path)
                        <div class="mb-2 text-sm text-blue-600">
                            Current: <a href="{{ route('admin.post-departure.download-license', $postDeparture) }}" class="underline">Download</a>
                        </div>
                    @endif
                    <input type="file"
                           name="foreign_license"
                           id="foreign_license"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Upload driving or professional license (PDF, JPG, PNG)</p>
                </div>

                <!-- Final Contract -->
                <div class="md:col-span-2">
                    <label for="final_contract" class="block text-gray-700 font-medium mb-2">
                        Final Employment Contract
                    </label>
                    @if(isset($postDeparture) && $postDeparture->final_contract_path)
                        <div class="mb-2 text-sm text-blue-600">
                            Current: <a href="{{ route('admin.post-departure.download-contract', $postDeparture) }}" class="underline">Download</a>
                        </div>
                    @endif
                    <input type="file"
                           name="final_contract"
                           id="final_contract"
                           accept=".pdf"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Upload signed final employment contract (PDF only)</p>
                </div>
            </div>
        </div>

        <!-- Final Employment Details Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
                Final Employment Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Company Name -->
                <div>
                    <label for="company_name" class="block text-gray-700 font-medium mb-2">
                        Company Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="company_name"
                           id="company_name"
                           value="{{ old('company_name', $postDeparture->company_name ?? '') }}"
                           required
                           placeholder="Actual employing company name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Employer Name (Contact Person) -->
                <div>
                    <label for="employer_name" class="block text-gray-700 font-medium mb-2">
                        Employer Contact Person
                    </label>
                    <input type="text"
                           name="employer_name"
                           id="employer_name"
                           value="{{ old('employer_name', $postDeparture->employer_name ?? '') }}"
                           placeholder="Name of employer/supervisor"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Employer Designation -->
                <div>
                    <label for="employer_designation" class="block text-gray-700 font-medium mb-2">
                        Employer Designation
                    </label>
                    <input type="text"
                           name="employer_designation"
                           id="employer_designation"
                           value="{{ old('employer_designation', $postDeparture->employer_designation ?? '') }}"
                           placeholder="e.g., HR Manager, Site Supervisor"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Employer Contact -->
                <div>
                    <label for="employer_contact" class="block text-gray-700 font-medium mb-2">
                        Employer Contact Number
                    </label>
                    <input type="text"
                           name="employer_contact"
                           id="employer_contact"
                           value="{{ old('employer_contact', $postDeparture->employer_contact ?? '') }}"
                           placeholder="Phone or mobile number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Work Location -->
                <div>
                    <label for="work_location" class="block text-gray-700 font-medium mb-2">
                        Work Location
                    </label>
                    <input type="text"
                           name="work_location"
                           id="work_location"
                           value="{{ old('work_location', $postDeparture->work_location ?? '') }}"
                           placeholder="City or site location"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Job Commencement Date -->
                <div>
                    <label for="job_commencement_date" class="block text-gray-700 font-medium mb-2">
                        Job Commencement Date
                    </label>
                    <input type="date"
                           name="job_commencement_date"
                           id="job_commencement_date"
                           value="{{ old('job_commencement_date', $postDeparture->job_commencement_date ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Final Salary -->
                <div>
                    <label for="final_salary" class="block text-gray-700 font-medium mb-2">
                        Final Salary Amount
                    </label>
                    <input type="number"
                           name="final_salary"
                           id="final_salary"
                           value="{{ old('final_salary', $postDeparture->final_salary ?? '') }}"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Salary Currency -->
                <div>
                    <label for="salary_currency" class="block text-gray-700 font-medium mb-2">
                        Salary Currency
                    </label>
                    <select name="salary_currency"
                            id="salary_currency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="PKR" {{ old('salary_currency', $postDeparture->salary_currency ?? 'PKR') === 'PKR' ? 'selected' : '' }}>PKR - Pakistani Rupee</option>
                        <option value="SAR" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal</option>
                        <option value="AED" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'AED' ? 'selected' : '' }}>AED - UAE Dirham</option>
                        <option value="OMR" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'OMR' ? 'selected' : '' }}>OMR - Omani Rial</option>
                        <option value="QAR" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'QAR' ? 'selected' : '' }}>QAR - Qatari Riyal</option>
                        <option value="BHD" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'BHD' ? 'selected' : '' }}>BHD - Bahraini Dinar</option>
                        <option value="KWD" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'KWD' ? 'selected' : '' }}>KWD - Kuwaiti Dinar</option>
                        <option value="USD" {{ old('salary_currency', $postDeparture->salary_currency ?? '') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                    </select>
                </div>
            </div>

            <!-- Final Job Terms -->
            <div class="mt-6">
                <label for="final_job_terms" class="block text-gray-700 font-medium mb-2">
                    Final Job Terms & Conditions
                </label>
                <textarea name="final_job_terms"
                          id="final_job_terms"
                          rows="4"
                          placeholder="Working hours, overtime policy, leave entitlement, benefits, etc."
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('final_job_terms', $postDeparture->final_job_terms ?? '') }}</textarea>
            </div>

            <!-- Special Conditions -->
            <div class="mt-4">
                <label for="special_conditions" class="block text-gray-700 font-medium mb-2">
                    Special Conditions or Notes
                </label>
                <textarea name="special_conditions"
                          id="special_conditions"
                          rows="3"
                          placeholder="Any special conditions, bonuses, or important notes"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('special_conditions', $postDeparture->special_conditions ?? '') }}</textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('admin.candidates.show', $candidate) }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                {{ isset($postDeparture) ? 'Update Post-Departure Details' : 'Save Post-Departure Details' }}
            </button>
        </div>
    </form>

    <!-- Company SWITCH Tracking (if employment history exists) -->
    @if(isset($employmentHistories) && $employmentHistories->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
            Company SWITCH History
        </h2>

        <div class="space-y-4">
            @foreach($employmentHistories as $history)
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-gray-600 text-sm">Company:</span>
                        <p class="font-semibold">{{ $history->company_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600 text-sm">Duration:</span>
                        <p class="font-semibold">{{ $history->start_date->format('M Y') }} - {{ $history->end_date ? $history->end_date->format('M Y') : 'Present' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600 text-sm">Reason for Switch:</span>
                        <p class="font-semibold">{{ $history->switch_reason ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($employmentHistories->count() < 2)
        <div class="mt-4">
            <a href="{{ route('admin.employment-history.create', $candidate) }}"
               class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                Record Company Switch
            </a>
        </div>
        @else
        <div class="mt-4 text-sm text-gray-600">
            Maximum of 2 company switches tracked. No more switches can be recorded.
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
