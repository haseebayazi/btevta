@extends('layouts.app')
@section('title', 'Registration Allocation - ' . $candidate->name)
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6" x-data="allocationForm()">
    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <nav class="flex items-center text-sm text-gray-500 mb-2" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Dashboard</a>
                <i class="fas fa-chevron-right mx-2 text-xs text-gray-400"></i>
                <a href="{{ route('registration.index') }}" class="hover:text-blue-600 transition">Registration</a>
                <i class="fas fa-chevron-right mx-2 text-xs text-gray-400"></i>
                <a href="{{ route('registration.show', $candidate->id) }}" class="hover:text-blue-600 transition">{{ $candidate->btevta_id }}</a>
                <i class="fas fa-chevron-right mx-2 text-xs text-gray-400"></i>
                <span class="text-gray-900 font-medium">Allocation</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900">Registration Allocation</h1>
            <p class="text-gray-500 mt-1">{{ $candidate->name }} &middot; {{ $candidate->btevta_id }}</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <a href="{{ route('registration.show', $candidate->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-3 text-green-500"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
            <span class="font-semibold">Please fix the following errors:</span>
        </div>
        <ul class="list-disc list-inside text-sm space-y-1 ml-6">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Candidate Info Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-id-card text-blue-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">TheLeap ID</p>
                    <p class="font-semibold text-gray-900 font-mono truncate">{{ $candidate->btevta_id }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-green-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">Candidate</p>
                    <p class="font-semibold text-gray-900 truncate">{{ $candidate->name }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-id-badge text-purple-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">CNIC</p>
                    <p class="font-semibold text-gray-900 font-mono truncate">{{ $candidate->cnic ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-signal text-amber-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('registration.store-allocation', $candidate->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column (Main Form) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Section 1: Allocation Details --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-map-marker-alt text-white text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Allocation Details</h2>
                                <p class="text-sm text-gray-600">Assign campus, program, trade, and partners</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Campus --}}
                            <div>
                                <label for="campus_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Campus <span class="text-red-500">*</span>
                                </label>
                                <select name="campus_id" id="campus_id"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('campus_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->id }}" {{ old('campus_id', $candidate->campus_id) == $campus->id ? 'selected' : '' }}>
                                            {{ $campus->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('campus_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Program --}}
                            <div>
                                <label for="program_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Program <span class="text-red-500">*</span>
                                </label>
                                <select name="program_id" id="program_id"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('program_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Program</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" {{ old('program_id', $candidate->program_id) == $program->id ? 'selected' : '' }}>
                                            {{ $program->name }} ({{ $program->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('program_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Trade --}}
                            <div>
                                <label for="trade_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Trade <span class="text-red-500">*</span>
                                </label>
                                <select name="trade_id" id="trade_id"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('trade_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Trade</option>
                                    @foreach($trades as $trade)
                                        <option value="{{ $trade->id }}" {{ old('trade_id', $candidate->trade_id) == $trade->id ? 'selected' : '' }}>
                                            {{ $trade->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('trade_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- OEP --}}
                            <div>
                                <label for="oep_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    OEP <span class="text-gray-400 font-normal">(Optional)</span>
                                </label>
                                <select name="oep_id" id="oep_id"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('oep_id') border-red-500 @enderror">
                                    <option value="">Select OEP</option>
                                    @foreach($oeps as $oep)
                                        <option value="{{ $oep->id }}" {{ old('oep_id', $candidate->oep_id) == $oep->id ? 'selected' : '' }}>
                                            {{ $oep->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('oep_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Implementing Partner --}}
                            <div class="md:col-span-2">
                                <label for="implementing_partner_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Implementing Partner <span class="text-gray-400 font-normal">(Optional)</span>
                                </label>
                                <select name="implementing_partner_id" id="implementing_partner_id"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('implementing_partner_id') border-red-500 @enderror">
                                    <option value="">Select Implementing Partner</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ old('implementing_partner_id', $candidate->implementing_partner_id) == $partner->id ? 'selected' : '' }}>
                                            {{ $partner->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('implementing_partner_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-5 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                                <div class="text-sm text-blue-800">
                                    <strong>Auto-Batch:</strong> A batch will be automatically assigned based on Campus + Program + Trade combination.
                                    Current batch size: <strong>{{ config('wasl.batch_size', 25) }}</strong> candidates.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Course Assignment --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-yellow-50">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-book text-white text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Course Assignment</h2>
                                <p class="text-sm text-gray-600">Select course and set training dates</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-5">
                            {{-- Course --}}
                            <div>
                                <label for="course_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Course <span class="text-red-500">*</span>
                                </label>
                                <select name="course_id" id="course_id" x-model="selectedCourse"
                                    @change="updateEndDate()"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('course_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}"
                                            data-duration="{{ $course->duration_days }}"
                                            data-type="{{ $course->training_type }}"
                                            {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->name }} ({{ $course->duration_days }} days &middot; {{ ucfirst(str_replace('_', ' ', $course->training_type)) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {{-- Start Date --}}
                                <div>
                                    <label for="course_start_date" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                        Start Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="course_start_date" id="course_start_date"
                                        x-model="startDate" @change="updateEndDate()"
                                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('course_start_date') border-red-500 @enderror"
                                        value="{{ old('course_start_date', now()->format('Y-m-d')) }}"
                                        min="{{ now()->format('Y-m-d') }}" required>
                                    @error('course_start_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- End Date --}}
                                <div>
                                    <label for="course_end_date" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                        End Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="course_end_date" id="course_end_date"
                                        x-model="endDate"
                                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('course_end_date') border-red-500 @enderror"
                                        value="{{ old('course_end_date') }}" required>
                                    @error('course_end_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Next of Kin --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-users text-white text-sm"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Next of Kin</h2>
                                <p class="text-sm text-gray-600">Contact and financial account details for remittance</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        {{-- Personal Details --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Name --}}
                            <div>
                                <label for="nok_name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nok_name" id="nok_name"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_name') border-red-500 @enderror"
                                    value="{{ old('nok_name', $candidate->nextOfKin?->name) }}" required>
                                @error('nok_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Relationship --}}
                            <div>
                                <label for="nok_relationship" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Relationship <span class="text-red-500">*</span>
                                </label>
                                <select name="nok_relationship" id="nok_relationship"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_relationship') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Relationship</option>
                                    @foreach($relationships as $key => $label)
                                        <option value="{{ $key }}" {{ old('nok_relationship', $candidate->nextOfKin?->relationship) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nok_relationship')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- CNIC --}}
                            <div>
                                <label for="nok_cnic" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    CNIC <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nok_cnic" id="nok_cnic"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_cnic') border-red-500 @enderror"
                                    value="{{ old('nok_cnic', $candidate->nextOfKin?->cnic) }}"
                                    maxlength="13" placeholder="1234567890123"
                                    x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').substring(0, 13)"
                                    required>
                                <p class="mt-1 text-xs text-gray-500">13-digit CNIC number without dashes</p>
                                @error('nok_cnic')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label for="nok_phone" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nok_phone" id="nok_phone"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_phone') border-red-500 @enderror"
                                    value="{{ old('nok_phone', $candidate->nextOfKin?->phone) }}" required>
                                @error('nok_phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Address --}}
                            <div class="md:col-span-2">
                                <label for="nok_address" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Address <span class="text-gray-400 font-normal">(Optional)</span>
                                </label>
                                <textarea name="nok_address" id="nok_address" rows="2"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_address') border-red-500 @enderror">{{ old('nok_address', $candidate->nextOfKin?->address) }}</textarea>
                                @error('nok_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Financial Details Divider --}}
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white px-3 text-sm text-gray-500 flex items-center">
                                    <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>
                                    Financial Account Details (for remittance)
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Payment Method --}}
                            <div>
                                <label for="nok_payment_method_id" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <select name="nok_payment_method_id" id="nok_payment_method_id"
                                    x-model="paymentMethod"
                                    @change="toggleBankName()"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_payment_method_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Payment Method</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}"
                                            data-requires-bank="{{ $method->requires_bank_name ? 'true' : 'false' }}"
                                            {{ old('nok_payment_method_id', $candidate->nextOfKin?->payment_method_id) == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nok_payment_method_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Account Number --}}
                            <div>
                                <label for="nok_account_number" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Account Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nok_account_number" id="nok_account_number"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_account_number') border-red-500 @enderror"
                                    value="{{ old('nok_account_number', $candidate->nextOfKin?->account_number) }}" required>
                                @error('nok_account_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Bank Name (conditional) --}}
                            <div x-show="showBankName" x-transition id="bank_name_group">
                                <label for="nok_bank_name" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    Bank Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nok_bank_name" id="nok_bank_name"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('nok_bank_name') border-red-500 @enderror"
                                    value="{{ old('nok_bank_name', $candidate->nextOfKin?->bank_name) }}"
                                    :required="showBankName">
                                @error('nok_bank_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ID Card Upload --}}
                            <div>
                                <label for="nok_id_card" class="block text-sm font-semibold text-gray-900 mb-1.5">
                                    ID Card Copy <span class="text-gray-400 font-normal">(Optional)</span>
                                </label>
                                <input type="file" name="nok_id_card" id="nok_id_card"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                        file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100 @error('nok_id_card') border-red-500 @enderror">
                                <p class="mt-1 text-xs text-gray-500">PDF, JPG, JPEG, PNG - Max 5MB</p>
                                @error('nok_id_card')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @if($candidate->nextOfKin?->id_card_path)
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i> ID Card Uploaded
                                        </span>
                                        <a href="{{ $candidate->nextOfKin->id_card_url }}" target="_blank"
                                            class="text-sm text-blue-600 hover:text-blue-800 transition">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column (Sidebar) --}}
            <div class="space-y-6">
                {{-- Registration Summary --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 sticky top-20">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-slate-50">
                        <h3 class="text-base font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-clipboard-list mr-2 text-gray-500"></i>
                            Registration Summary
                        </h3>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">Candidate</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $candidate->name }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">TheLeap ID</span>
                                <span class="text-sm font-semibold text-gray-900 font-mono">{{ $candidate->btevta_id }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">Phone</span>
                                <span class="text-sm text-gray-900">{{ $candidate->phone ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">Father Name</span>
                                <span class="text-sm text-gray-900">{{ $candidate->father_name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">Current Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-500">New Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-arrow-right mr-1 text-xs"></i> Registered
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Auto-Batch Info --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="text-base font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-layer-group mr-2 text-purple-500"></i>
                            Auto-Batch Info
                        </h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-600 mb-3">
                            The candidate will be automatically assigned to a batch based on:
                        </p>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center text-gray-700">
                                <i class="fas fa-check text-green-500 mr-2 text-xs"></i> Selected Campus
                            </li>
                            <li class="flex items-center text-gray-700">
                                <i class="fas fa-check text-green-500 mr-2 text-xs"></i> Selected Program
                            </li>
                            <li class="flex items-center text-gray-700">
                                <i class="fas fa-check text-green-500 mr-2 text-xs"></i> Selected Trade
                            </li>
                        </ul>
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500">
                                Batch size: <strong class="text-gray-700">{{ config('wasl.batch_size', 25) }}</strong> candidates
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Available sizes: {{ implode(', ', config('wasl.allowed_batch_sizes', [20, 25, 30])) }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Submit Actions --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2"></i> Complete Registration
                    </button>
                    <a href="{{ route('registration.show', $candidate->id) }}"
                        class="w-full mt-3 px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition flex items-center justify-center text-sm">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function allocationForm() {
    return {
        selectedCourse: '{{ old('course_id', '') }}',
        startDate: '{{ old('course_start_date', now()->format('Y-m-d')) }}',
        endDate: '{{ old('course_end_date', '') }}',
        paymentMethod: '{{ old('nok_payment_method_id', $candidate->nextOfKin?->payment_method_id ?? '') }}',
        showBankName: false,

        init() {
            this.toggleBankName();
            this.updateEndDate();
        },

        updateEndDate() {
            const courseSelect = document.getElementById('course_id');
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            const duration = parseInt(selectedOption?.getAttribute('data-duration')) || 0;

            if (this.startDate && duration > 0) {
                const start = new Date(this.startDate);
                start.setDate(start.getDate() + duration);
                this.endDate = start.toISOString().split('T')[0];
            }
        },

        toggleBankName() {
            const paymentSelect = document.getElementById('nok_payment_method_id');
            const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
            this.showBankName = selectedOption?.getAttribute('data-requires-bank') === 'true';
        }
    };
}
</script>
@endpush
@endsection
