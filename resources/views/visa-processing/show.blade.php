@extends('layouts.app')

@section('title', 'Visa Processing - ' . $candidate->name)

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('visa-processing.index') }}" class="hover:text-blue-600">Visa Processing</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">{{ $candidate->btevta_id }}</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Visa Processing</h2>
            <p class="text-gray-500 text-sm mt-1">{{ $candidate->name }} - {{ $candidate->trade->name ?? 'N/A' }}</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('visa-processing.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="{{ route('visa-processing.timeline', $candidate) }}" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-history mr-1"></i> Timeline
            </a>
            @if($candidate->visaProcess && $candidate->visaProcess->overall_status !== 'completed')
                <a href="{{ route('visa-processing.edit', $candidate) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            @endif
            <a href="{{ route('visa-processing.hierarchical-dashboard') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-th-large mr-1"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button @click="show = false" class="text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button @click="show = false" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
    </div>
    @endif

    @php
        $visaProcess = $candidate->visaProcess;
        $stages = \App\Models\VisaProcess::getStages();
        $detailStages = \App\Models\VisaProcess::DETAIL_STAGES;
    @endphp

    {{-- Progress Bar --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <h5 class="font-semibold text-gray-800"><i class="fas fa-tasks mr-2 text-blue-500"></i>Processing Progress</h5>
                @php
                    $statusColors = [
                        'secondary' => 'bg-gray-100 text-gray-700',
                        'info' => 'bg-blue-100 text-blue-700',
                        'warning' => 'bg-yellow-100 text-yellow-700',
                        'primary' => 'bg-indigo-100 text-indigo-700',
                        'success' => 'bg-green-100 text-green-700',
                    ];
                    $currentStageInfo = $visaProcess->getCurrentStageInfo();
                    $badgeColor = $statusColors[$currentStageInfo['color']] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $badgeColor }}">
                    {{ $currentStageInfo['label'] }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-5 mb-4">
                <div class="bg-green-500 h-5 rounded-full text-center text-white text-xs leading-5 font-medium transition-all duration-500" style="width: {{ $visaProcess->progress_percentage ?? 10 }}%">
                    {{ $visaProcess->progress_percentage ?? 10 }}%
                </div>
            </div>
            <div class="grid grid-cols-5 lg:grid-cols-10 gap-1 text-center">
                @foreach($stages as $key => $stage)
                    @if($key !== 'completed')
                    @php
                        $isCompleted = $visaProcess ? $visaProcess->isStageCompleted($key) : false;
                        $isCurrent = $visaProcess && $visaProcess->overall_status === $key;
                    @endphp
                    <div class="flex flex-col items-center">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white {{ $isCompleted ? 'bg-green-500' : ($isCurrent ? 'bg-blue-600' : 'bg-gray-300') }}">
                            @if($isCompleted)
                                <i class="fas fa-check text-xs"></i>
                            @else
                                {{ $stage['order'] }}
                            @endif
                        </div>
                        <p class="text-[10px] mt-1 {{ $isCompleted || $isCurrent ? 'font-bold text-gray-800' : 'text-gray-400' }}">{{ $stage['label'] }}</p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Candidate Info --}}
        <div class="space-y-6">
            {{-- Candidate Card --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="bg-blue-600 text-white px-5 py-3">
                    <h5 class="font-semibold"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                </div>
                <div class="p-5">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">TheLeap ID</span>
                            <span class="font-mono font-bold text-sm">{{ $candidate->btevta_id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Name</span>
                            <span class="text-sm font-medium">{{ $candidate->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">CNIC</span>
                            <span class="font-mono text-sm">{{ $candidate->cnic }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Passport</span>
                            <span class="font-mono text-sm">{{ $candidate->passport_number ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Trade</span>
                            <span class="text-sm">{{ $candidate->trade->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Campus</span>
                            <span class="text-sm">{{ $candidate->campus->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">OEP</span>
                            <span class="text-sm">{{ $candidate->oep->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Key Numbers --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-key mr-2 text-yellow-500"></i>Key Numbers</h5>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="text-gray-500 text-xs block mb-1">E-Number</label>
                        <span class="font-mono text-lg font-semibold text-gray-800">{{ $visaProcess->enumber ?? 'Not Generated' }}</span>
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs block mb-1">PTN Number</label>
                        <span class="font-mono text-lg font-semibold text-gray-800">{{ $visaProcess->ptn_number ?? 'Not Issued' }}</span>
                    </div>
                    <div>
                        <label class="text-gray-500 text-xs block mb-1">Visa Number</label>
                        <span class="font-mono text-lg font-semibold text-gray-800">{{ $visaProcess->visa_number ?? 'Not Issued' }}</span>
                    </div>
                </div>
            </div>

            {{-- Complete Button --}}
            @if($visaProcess && $visaProcess->ticket_uploaded && $visaProcess->overall_status !== 'completed')
                <div class="bg-white rounded-xl shadow-sm border-2 border-green-300 overflow-hidden">
                    <div class="p-5 text-center">
                        <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                        <h5 class="font-semibold text-gray-800 mb-2">Ready to Complete</h5>
                        <p class="text-gray-500 text-sm mb-4">All stages are completed. Mark this visa process as complete.</p>
                        <form action="{{ route('visa-processing.complete', $candidate) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold" onclick="return confirm('Mark visa process as complete?')">
                                <i class="fas fa-check-double mr-2"></i>Complete Process
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column - Stages (2/3 width) --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Stage 1: Interview & Trade Test --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-user-tie mr-2 text-blue-500"></i>1. Interview & Trade Test</h5>
                    <div class="flex items-center space-x-2">
                        @php
                            $interviewDone = $visaProcess->interview_completed && $visaProcess->trade_test_completed;
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $interviewDone ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $interviewDone ? 'Completed' : 'Pending' }}
                        </span>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h6 class="font-semibold text-gray-700 mb-2">Interview</h6>
                            <div class="space-y-1.5 text-sm">
                                <p><span class="text-gray-500">Date:</span> <span class="font-medium">{{ $visaProcess->interview_date ? $visaProcess->interview_date->format('d M Y') : 'Not Scheduled' }}</span></p>
                                <p><span class="text-gray-500">Status:</span>
                                    @php $iStatus = $visaProcess->interview_status ?? 'pending'; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $iStatus === 'passed' || $iStatus === 'completed' ? 'bg-green-100 text-green-700' : ($iStatus === 'failed' ? 'bg-red-100 text-red-700' : ($iStatus === 'scheduled' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')) }}">
                                        {{ ucfirst($iStatus) }}
                                    </span>
                                </p>
                                @if($visaProcess->interview_remarks)
                                    <p class="text-gray-500 text-xs"><span class="font-medium">Remarks:</span> {{ $visaProcess->interview_remarks }}</p>
                                @endif
                            </div>
                            <a href="{{ route('visa-processing.stage-details', [$visaProcess, 'interview']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs mt-2">
                                <i class="fas fa-cog mr-1"></i> Manage Stage
                            </a>
                        </div>
                        <div>
                            <h6 class="font-semibold text-gray-700 mb-2">Trade Test</h6>
                            <div class="space-y-1.5 text-sm">
                                <p><span class="text-gray-500">Date:</span> <span class="font-medium">{{ $visaProcess->trade_test_date ? $visaProcess->trade_test_date->format('d M Y') : 'Not Scheduled' }}</span></p>
                                <p><span class="text-gray-500">Status:</span>
                                    @php $ttStatus = $visaProcess->trade_test_status ?? 'pending'; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $ttStatus === 'passed' || $ttStatus === 'completed' ? 'bg-green-100 text-green-700' : ($ttStatus === 'failed' ? 'bg-red-100 text-red-700' : ($ttStatus === 'scheduled' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')) }}">
                                        {{ ucfirst($ttStatus) }}
                                    </span>
                                </p>
                                @if($visaProcess->trade_test_remarks)
                                    <p class="text-gray-500 text-xs"><span class="font-medium">Remarks:</span> {{ $visaProcess->trade_test_remarks }}</p>
                                @endif
                            </div>
                            <a href="{{ route('visa-processing.stage-details', [$visaProcess, 'trade_test']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs mt-2">
                                <i class="fas fa-cog mr-1"></i> Manage Stage
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 2: Takamol Test --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-certificate mr-2 text-purple-500"></i>2. Takamol Test</h5>
                    <div class="flex items-center space-x-2">
                        @php $takamolStatus = $visaProcess->takamol_status ?? 'pending'; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $takamolStatus === 'completed' ? 'bg-green-100 text-green-700' : ($takamolStatus === 'scheduled' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($takamolStatus) }}
                        </span>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Test Date:</span> <span class="font-medium">{{ $visaProcess->takamol_date ? $visaProcess->takamol_date->format('d M Y') : 'Not Scheduled' }}</span></p>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            @php
                                $takamolDetails = $visaProcess->takamol_details;
                            @endphp
                            @if(!empty($takamolDetails['center']))
                                <p><span class="text-gray-500">Center:</span> <span class="font-medium">{{ $takamolDetails['center'] }}</span></p>
                            @endif
                            @if(!empty($takamolDetails['evidence_path']))
                                <p><span class="text-gray-500">Evidence:</span> <span class="text-green-600 text-xs"><i class="fas fa-check-circle mr-1"></i>Uploaded</span></p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('visa-processing.stage-details', [$visaProcess, 'takamol']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs mt-3">
                        <i class="fas fa-cog mr-1"></i> Manage Stage
                    </a>
                </div>
            </div>

            {{-- Stage 3: Medical/GAMCA --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-heartbeat mr-2 text-red-500"></i>3. Medical (GAMCA)</h5>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $visaProcess->medical_completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $visaProcess->medical_status === 'fit' ? 'Fit' : ($visaProcess->medical_status === 'unfit' ? 'Unfit' : ($visaProcess->medical_status === 'completed' ? 'Completed' : 'Pending')) }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Test Date:</span> <span class="font-medium">{{ $visaProcess->medical_date ? $visaProcess->medical_date->format('d M Y') : 'Not Scheduled' }}</span></p>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            @php
                                $medicalDetails = $visaProcess->medical_details;
                            @endphp
                            @if(!empty($medicalDetails['center']))
                                <p><span class="text-gray-500">Center:</span> <span class="font-medium">{{ $medicalDetails['center'] }}</span></p>
                            @endif
                            @if(!empty($medicalDetails['evidence_path']))
                                <p><span class="text-gray-500">Certificate:</span> <span class="text-green-600 text-xs"><i class="fas fa-check-circle mr-1"></i>Uploaded</span></p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('visa-processing.stage-details', [$visaProcess, 'medical']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs mt-3">
                        <i class="fas fa-cog mr-1"></i> Manage Stage
                    </a>
                </div>
            </div>

            {{-- Stage 4: E-Number --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-hashtag mr-2 text-indigo-500"></i>4. E-Number Generation</h5>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $visaProcess->enumber ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $visaProcess->enumber ? 'Generated' : 'Pending' }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">E-Number:</span> <span class="font-mono text-lg font-semibold">{{ $visaProcess->enumber ?? 'Not Generated' }}</span></p>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Status:</span>
                                @php $eStatus = $visaProcess->enumber ? 'generated' : 'pending'; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $eStatus === 'generated' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($eStatus) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 5: Biometrics/Etimad --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-fingerprint mr-2 text-teal-500"></i>5. Biometrics (Etimad)</h5>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $visaProcess->biometric_completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $visaProcess->biometric_completed ? 'Completed' : 'Pending' }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Appointment ID:</span> <span class="font-mono font-medium">{{ $visaProcess->etimad_appointment_id ?? 'N/A' }}</span></p>
                            <p><span class="text-gray-500">Appointment Date:</span> <span class="font-medium">{{ $visaProcess->biometric_date ? $visaProcess->biometric_date->format('d M Y') : 'Not Scheduled' }}</span></p>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            @php
                                $bioDetails = $visaProcess->biometric_details;
                            @endphp
                            @if(!empty($bioDetails['center']))
                                <p><span class="text-gray-500">Center:</span> <span class="font-medium">{{ $bioDetails['center'] }}</span></p>
                            @endif
                            <p><span class="text-gray-500">Status:</span>
                                @php $bStatus = $visaProcess->biometric_status ?? 'pending'; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $bStatus === 'completed' ? 'bg-green-100 text-green-700' : ($bStatus === 'failed' ? 'bg-red-100 text-red-700' : ($bStatus === 'scheduled' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')) }}">
                                    {{ ucfirst($bStatus) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('visa-processing.stage-details', [$visaProcess, 'biometric']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs mt-3">
                        <i class="fas fa-cog mr-1"></i> Manage Stage
                    </a>
                </div>
            </div>

            {{-- Stage 6: Visa Application --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-passport mr-2 text-amber-500"></i>6. Visa Application</h5>
                    @php
                        $appStatus = $visaProcess->visa_application_status?->value ?? 'not_applied';
                        $issuedStatus = $visaProcess->visa_issued_status?->value ?? 'pending';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appStatus === 'applied' ? 'bg-blue-100 text-blue-700' : ($appStatus === 'refused' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst(str_replace('_', ' ', $appStatus)) }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Application Status:</span> <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $appStatus)) }}</span></p>
                            <p><span class="text-gray-500">Visa Number:</span> <span class="font-mono font-medium">{{ $visaProcess->visa_number ?? 'Not Issued' }}</span></p>
                            <p><span class="text-gray-500">Visa Date:</span> <span class="font-medium">{{ $visaProcess->visa_date ? $visaProcess->visa_date->format('d M Y') : 'N/A' }}</span></p>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Issued Status:</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $issuedStatus === 'confirmed' ? 'bg-green-100 text-green-700' : ($issuedStatus === 'refused' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ ucfirst($issuedStatus) }}
                                </span>
                            </p>
                            <p><span class="text-gray-500">PTN Number:</span> <span class="font-mono font-medium">{{ $visaProcess->ptn_number ?? 'Not Issued' }}</span></p>
                        </div>
                    </div>
                    <a href="{{ route('visa-processing.stage-details', [$visaProcess, 'visa_application']) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs mt-3">
                        <i class="fas fa-cog mr-1"></i> Manage Stage
                    </a>
                </div>
            </div>

            {{-- Stage 7: Ticket & Travel --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center justify-between">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-plane-departure mr-2 text-sky-500"></i>7. Ticket & Travel Plan</h5>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $visaProcess->ticket_uploaded ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $visaProcess->ticket_uploaded ? 'Uploaded' : 'Pending' }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5 text-sm">
                            <p><span class="text-gray-500">Ticket Date:</span> <span class="font-medium">{{ $visaProcess->ticket_date ? $visaProcess->ticket_date->format('d M Y') : 'N/A' }}</span></p>
                            @if($visaProcess->ticket_path)
                                <p><span class="text-gray-500">Ticket:</span>
                                    <a href="{{ route('secure-file.download', $visaProcess->ticket_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">
                                        <i class="fas fa-download mr-1"></i>View Ticket
                                    </a>
                                </p>
                            @endif
                        </div>
                        <div class="space-y-1.5 text-sm">
                            @if($visaProcess->travel_plan_path)
                                <p><span class="text-gray-500">Travel Plan:</span>
                                    <a href="{{ route('secure-file.download', $visaProcess->travel_plan_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">
                                        <i class="fas fa-download mr-1"></i>View Plan
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remarks --}}
            @if($visaProcess->remarks)
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-gray-500"></i>Remarks</h5>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-700">{{ $visaProcess->remarks }}</p>
                </div>
            </div>
            @endif

            {{-- Failure Info --}}
            @if($visaProcess->failed_at)
            <div class="bg-white rounded-xl shadow-sm border-2 border-red-300 overflow-hidden">
                <div class="bg-red-600 text-white px-5 py-3">
                    <h5 class="font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>Process Failed</h5>
                </div>
                <div class="p-5 space-y-2 text-sm">
                    <p><span class="text-gray-500">Failed At:</span> <span class="font-medium">{{ $visaProcess->failed_at->format('d M Y H:i') }}</span></p>
                    <p><span class="text-gray-500">Failed Stage:</span> <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $visaProcess->failed_stage ?? 'Unknown')) }}</span></p>
                    @if($visaProcess->failure_reason)
                        <p><span class="text-gray-500">Reason:</span> <span class="font-medium text-red-700">{{ $visaProcess->failure_reason }}</span></p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
