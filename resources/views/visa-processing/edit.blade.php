@extends('layouts.app')

@section('title', 'Edit Visa Process - ' . $candidate->name)

@section('content')
@php $visaProcess = $candidate->visaProcess; @endphp
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('visa-processing.index') }}" class="hover:text-blue-600">Visa Processing</a>
                <span class="mx-1">/</span>
                <a href="{{ route('visa-processing.show', $candidate) }}" class="hover:text-blue-600">{{ $candidate->btevta_id }}</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Edit</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Edit Visa Process</h2>
            <p class="text-gray-500 text-sm">{{ $candidate->name }} &mdash; {{ $candidate->trade->name ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('visa-processing.show', $candidate) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between"
         x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button @click="show = false" class="ml-4 text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between"
         x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button @click="show = false" class="ml-4 text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
    </div>
    @endif
    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
        <ul class="list-disc list-inside space-y-1 text-sm">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex gap-6">

        {{-- Left Sidebar --}}
        <div class="hidden lg:block w-64 flex-shrink-0">
            {{-- Candidate Card --}}
            <div class="bg-white rounded-xl shadow-sm border mb-4">
                <div class="bg-blue-600 text-white px-4 py-3 rounded-t-xl">
                    <h5 class="font-semibold text-sm"><i class="fas fa-user mr-2"></i>Candidate</h5>
                </div>
                <div class="p-4 space-y-1 text-sm">
                    <p class="font-semibold text-gray-900">{{ $candidate->name }}</p>
                    <p class="text-gray-500 font-mono">{{ $candidate->btevta_id }}</p>
                    <p class="text-gray-600">{{ $candidate->trade->name ?? 'N/A' }}</p>
                    <p class="text-gray-600">{{ $candidate->oep->name ?? 'No OEP' }}</p>
                </div>
            </div>

            {{-- Stage Navigation --}}
            <div class="bg-white rounded-xl shadow-sm border">
                <div class="px-4 py-3 border-b">
                    <h5 class="font-semibold text-sm text-gray-700"><i class="fas fa-list mr-2"></i>Quick Navigation</h5>
                </div>
                <div class="divide-y text-sm">
                    @php
                        $navItems = [
                            'stage-interview'   => ['label' => 'Interview & Trade Test', 'done' => $visaProcess->interview_completed],
                            'stage-takamol'     => ['label' => 'Takamol Test',           'done' => $visaProcess->takamol_status === 'completed'],
                            'stage-medical'     => ['label' => 'Medical (GAMCA)',         'done' => $visaProcess->medical_completed],
                            'stage-enumber'     => ['label' => 'E-Number',               'done' => (bool)$visaProcess->enumber],
                            'stage-biometric'   => ['label' => 'Biometrics (Etimad)',     'done' => $visaProcess->biometric_completed],
                            'stage-submission'  => ['label' => 'Visa Submission',         'done' => (bool)$visaProcess->visa_submission_date],
                            'stage-visa'        => ['label' => 'Visa Issuance',           'done' => $visaProcess->visa_issued],
                            'stage-ptn'         => ['label' => 'PTN Clearance',           'done' => $visaProcess->ptn_cleared],
                            'stage-protector'   => ['label' => 'Protector Clearance',     'done' => $visaProcess->protector_performed],
                            'stage-complete'    => ['label' => 'Complete & Departure',    'done' => $visaProcess->overall_status === 'completed'],
                        ];
                    @endphp
                    @foreach($navItems as $anchor => $item)
                    <a href="#{{ $anchor }}"
                       class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 transition-colors nav-link">
                        <span class="text-gray-700">{{ $item['label'] }}</span>
                        <i class="fas fa-{{ $item['done'] ? 'check text-green-500' : 'clock text-yellow-500' }} text-xs"></i>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 space-y-6 min-w-0">

            {{-- Stage 1: Interview & Trade Test --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-interview">
                <div class="bg-cyan-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-user-tie mr-2"></i>1. Interview & Trade Test</h5>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Interview --}}
                    <div>
                        <h6 class="font-semibold text-gray-700 border-b pb-2 mb-4">Interview</h6>
                        <form action="{{ route('visa-processing.update-interview', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                                <input type="date" name="interview_date" required
                                       value="{{ $visaProcess->interview_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="interview_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending"  {{ $visaProcess->interview_status === 'pending'  ? 'selected' : '' }}>Pending</option>
                                    <option value="passed"   {{ $visaProcess->interview_status === 'passed'   ? 'selected' : '' }}>Passed</option>
                                    <option value="failed"   {{ $visaProcess->interview_status === 'failed'   ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea name="interview_remarks" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">{{ $visaProcess->interview_remarks }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Evidence Attachment</label>
                                <input type="file" name="interview_evidence" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG — Max 5MB</p>
                                @if($visaProcess->interview_evidence_path)
                                <a href="{{ route('secure-file.download', $visaProcess->interview_evidence_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current evidence
                                </a>
                                @endif
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-save mr-2"></i> Save Interview
                            </button>
                        </form>
                    </div>
                    {{-- Trade Test --}}
                    <div>
                        <h6 class="font-semibold text-gray-700 border-b pb-2 mb-4">Trade Test</h6>
                        <form action="{{ route('visa-processing.update-trade-test', $candidate) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                                <input type="date" name="trade_test_date" required
                                       value="{{ $visaProcess->trade_test_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="trade_test_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending" {{ $visaProcess->trade_test_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="passed"  {{ $visaProcess->trade_test_status === 'passed'  ? 'selected' : '' }}>Passed</option>
                                    <option value="failed"  {{ $visaProcess->trade_test_status === 'failed'  ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea name="trade_test_remarks" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">{{ $visaProcess->trade_test_remarks }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-save mr-2"></i> Save Trade Test
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Stage 2: Takamol Test --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-takamol">
                <div class="bg-cyan-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-clipboard-check mr-2"></i>2. Takamol Test</h5>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-500 mb-4">
                        First an appointment is booked; the Takamol test is conducted 2&ndash;3 days later.
                        The result is simply pass or fail.
                    </p>
                    <form action="{{ route('visa-processing.update-takamol', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Date <span class="text-red-500">*</span></label>
                                <input type="date" name="takamol_date" required
                                       value="{{ $visaProcess->takamol_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Center</label>
                                <input type="text" name="takamol_center"
                                       value="{{ $visaProcess->takamol_center }}"
                                       placeholder="e.g., Takamol Center Lahore"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Slip</label>
                                <input type="file" name="takamol_appointment_slip" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($visaProcess->takamol_appointment_slip_path)
                                <a href="{{ route('secure-file.download', $visaProcess->takamol_appointment_slip_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current slip
                                </a>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Attachment</label>
                                <input type="file" name="takamol_result_file" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($visaProcess->takamol_result_path)
                                <a href="{{ route('secure-file.download', $visaProcess->takamol_result_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current result
                                </a>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="takamol_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending"   {{ $visaProcess->takamol_status === 'pending'   ? 'selected' : '' }}>Pending</option>
                                    <option value="completed" {{ $visaProcess->takamol_status === 'completed' ? 'selected' : '' }}>Pass</option>
                                    <option value="failed"    {{ $visaProcess->takamol_status === 'failed'    ? 'selected' : '' }}>Fail</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea name="takamol_remarks" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">{{ $visaProcess->takamol_remarks }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Takamol
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 3: Medical / GAMCA --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-medical">
                <div class="bg-cyan-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-heartbeat mr-2"></i>3. Medical (GAMCA)</h5>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-500 mb-4">
                        First an appointment is booked; the medical (GAMCA) test is conducted afterwards.
                        The result is simply fit or unfit.
                    </p>
                    <form action="{{ route('visa-processing.update-medical', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Date <span class="text-red-500">*</span></label>
                                <input type="date" name="medical_date" required
                                       value="{{ $visaProcess->medical_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Center</label>
                                <input type="text" name="medical_center"
                                       value="{{ $visaProcess->medical_center }}"
                                       placeholder="e.g., GAMCA Medical Center"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Slip</label>
                                <input type="file" name="medical_appointment_slip" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($visaProcess->medical_appointment_slip_path)
                                <a href="{{ route('secure-file.download', $visaProcess->medical_appointment_slip_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current slip
                                </a>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Attachment</label>
                                <input type="file" name="medical_result_file" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($visaProcess->medical_result_path)
                                <a href="{{ route('secure-file.download', $visaProcess->medical_result_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current result
                                </a>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="medical_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending" {{ $visaProcess->medical_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="fit"     {{ $visaProcess->medical_status === 'fit'     ? 'selected' : '' }}>Fit</option>
                                    <option value="unfit"   {{ $visaProcess->medical_status === 'unfit'   ? 'selected' : '' }}>Unfit</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea name="medical_remarks" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">{{ $visaProcess->medical_remarks }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Medical
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 4: E-Number --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-enumber">
                <div class="bg-cyan-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-hashtag mr-2"></i>4. E-Number Generation</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('visa-processing.update-enumber', $candidate) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-Number</label>
                                <input type="text" name="enumber" value="{{ $visaProcess->enumber }}"
                                       placeholder="Leave blank to auto-generate"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-400 mt-1">Leave blank to auto-generate</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Generation Date <span class="text-red-500">*</span></label>
                                <input type="date" name="enumber_date" required
                                       value="{{ $visaProcess->enumber_date?->format('Y-m-d') ?? date('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="enumber_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending"   {{ $visaProcess->enumber_status === 'pending'   ? 'selected' : '' }}>Pending</option>
                                    <option value="generated" {{ $visaProcess->enumber_status === 'generated' ? 'selected' : '' }}>Generated</option>
                                    <option value="verified"  {{ $visaProcess->enumber_status === 'verified'  ? 'selected' : '' }}>Verified</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Generate / Update E-Number
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 5: Biometrics / Etimad --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-biometric">
                <div class="bg-cyan-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-fingerprint mr-2"></i>5. Biometrics (Etimad)</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('visa-processing.update-biometric', $candidate) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Etimad Appointment ID</label>
                                <input type="text" name="etimad_appointment_id"
                                       value="{{ $visaProcess->etimad_appointment_id }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Date <span class="text-red-500">*</span></label>
                                <input type="date" name="biometric_date" required
                                       value="{{ $visaProcess->biometric_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Etimad Center</label>
                                <input type="text" name="etimad_center"
                                       value="{{ $visaProcess->etimad_center }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="biometric_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending"   {{ $visaProcess->biometric_status === 'pending'   ? 'selected' : '' }}>Pending</option>
                                    <option value="completed" {{ $visaProcess->biometric_status === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="failed"    {{ $visaProcess->biometric_status === 'failed'    ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea name="biometric_remarks" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">{{ $visaProcess->biometric_remarks }}</textarea>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Biometrics
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 6: Visa Documents Submission --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-submission">
                <div class="bg-yellow-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-file-alt mr-2"></i>6. Visa Documents Submission</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('visa-processing.update-visa-submission', $candidate) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Submission Date <span class="text-red-500">*</span></label>
                                <input type="date" name="visa_submission_date" required
                                       value="{{ $visaProcess->visa_submission_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Application Number</label>
                                <input type="text" name="visa_application_number"
                                       value="{{ $visaProcess->visa_application_number }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Embassy</label>
                                <input type="text" name="embassy" value="{{ $visaProcess->embassy }}"
                                       placeholder="e.g., Saudi Embassy Islamabad"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Submission Details
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 7: Visa Issuance --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-visa">
                <div class="bg-blue-600 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-passport mr-2"></i>7. Visa Issuance</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('visa-processing.update-visa', $candidate) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Visa Date <span class="text-red-500">*</span></label>
                                <input type="date" name="visa_date" required
                                       value="{{ $visaProcess->visa_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Visa Number <span class="text-red-500">*</span></label>
                                <input type="text" name="visa_number" required
                                       value="{{ $visaProcess->visa_number }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select name="visa_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending"  {{ $visaProcess->visa_status === 'pending'  ? 'selected' : '' }}>Pending</option>
                                    <option value="issued"   {{ $visaProcess->visa_status === 'issued'   ? 'selected' : '' }}>Issued</option>
                                    <option value="rejected" {{ $visaProcess->visa_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <input type="text" name="visa_remarks"
                                       value="{{ $visaProcess->visa_remarks }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Visa Details
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 8: PTN Clearance (Yes/No) --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-ptn">
                <div class="bg-blue-600 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-stamp mr-2"></i>8. PTN Clearance</h5>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-500 mb-4">
                        Record the Permission to Depart (PTN) clearance details for this candidate.
                    </p>
                    @if($visaProcess->ptn_cleared)
                    <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <span>PTN is confirmed performed.</span>
                    </div>
                    @endif
                    <form action="{{ route('visa-processing.update-ptn', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PTN Issue Date</label>
                                <input type="date" name="ptn_issue_date"
                                       value="{{ $visaProcess->ptn_issue_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PTN Performed? <span class="text-red-500">*</span></label>
                                <div class="flex items-center gap-4 mt-2">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="ptn_cleared" value="yes"
                                               {{ $visaProcess->ptn_cleared ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="ptn_cleared" value="no"
                                               {{ !$visaProcess->ptn_cleared ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Upload PTN</label>
                                <input type="file" name="ptn_document" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($visaProcess->ptn_document_path)
                                <a href="{{ route('secure-file.download', $visaProcess->ptn_document_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current PTN document
                                </a>
                                @endif
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Update PTN
                        </button>
                    </form>
                </div>
            </div>

            {{-- Stage 9: Protector Clearance --}}
            <div class="bg-white rounded-xl shadow-sm border" id="stage-protector">
                <div class="bg-yellow-500 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-shield-alt mr-2"></i>9. Protector Clearance</h5>
                </div>
                <div class="p-5">
                    @if($visaProcess->protector_performed)
                    <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">
                        <i class="fas fa-check-circle"></i>
                        <span>Protector clearance is confirmed performed.
                            @if($visaProcess->protector_submission_date)
                                &mdash; {{ $visaProcess->protector_submission_date->format('d M Y') }}
                            @endif
                        </span>
                    </div>
                    @endif
                    <form action="{{ route('visa-processing.update-protector-clearance', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Protector Submission Date</label>
                                <input type="date" name="protector_submission_date"
                                       value="{{ $visaProcess->protector_submission_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Protector Performed? <span class="text-red-500">*</span></label>
                                <div class="flex items-center gap-4 mt-2">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="protector_performed" value="yes"
                                               {{ $visaProcess->protector_performed ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="protector_performed" value="no"
                                               {{ !$visaProcess->protector_performed ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Upload Protector</label>
                                <input type="file" name="protector_document" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($visaProcess->protector_document_path)
                                <a href="{{ route('secure-file.download', $visaProcess->protector_document_path) }}" target="_blank"
                                   class="inline-flex items-center text-xs text-green-600 hover:text-green-800 mt-1">
                                    <i class="fas fa-paperclip mr-1"></i> View current protector document
                                </a>
                                @endif
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i> Update Protector Clearance
                        </button>
                    </form>
                </div>
            </div>

            {{-- Completion / Departure Hand-off --}}
            <div class="bg-white rounded-xl shadow-sm border-2 {{ $visaProcess->isReadyToComplete() ? 'border-green-300' : 'border-gray-200' }}" id="stage-complete">
                <div class="bg-green-600 text-white px-5 py-3 rounded-t-xl">
                    <h5 class="font-semibold"><i class="fas fa-flag-checkered mr-2"></i>Complete &amp; Send to Departure</h5>
                </div>
                <div class="p-5">
                    @if($visaProcess->overall_status === 'completed')
                        <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                            <i class="fas fa-check-circle"></i>
                            <span>Visa process is complete. This candidate is now in the Departure module.</span>
                        </div>
                    @elseif($visaProcess->isReadyToComplete())
                        <p class="text-gray-600 text-sm mb-4">All required stages are complete. Completing will move this candidate to the Departure module.</p>
                        <form action="{{ route('visa-processing.complete', $candidate) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors"
                                    onclick="return confirm('Mark visa process as complete and move candidate to Departure?')">
                                <i class="fas fa-check-double mr-2"></i> Complete &amp; Send to Departure
                            </button>
                        </form>
                    @else
                        <p class="text-gray-600 text-sm mb-3">Complete the following before this candidate can move to the Departure module:</p>
                        <ul class="space-y-1.5 text-sm">
                            @foreach($visaProcess->getOutstandingCompletionRequirements() as $requirement)
                                <li class="flex items-start text-gray-700">
                                    <i class="fas fa-circle-exclamation text-yellow-500 mr-2 mt-1 text-xs"></i>
                                    <span>{{ $requirement }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

        </div>{{-- end main content --}}
    </div>{{-- end flex --}}
</div>

@push('scripts')
<script>
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
</script>
@endpush
@endsection
