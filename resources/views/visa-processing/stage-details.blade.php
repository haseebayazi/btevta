@extends('layouts.app')

@section('title', 'Stage Details - ' . ucfirst(str_replace('_', ' ', $stage)))

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
        <span class="mx-1">/</span>
        <a href="{{ route('visa-processing.index') }}" class="hover:text-blue-600">Visa Processing</a>
        <span class="mx-1">/</span>
        <a href="{{ route('visa-processing.show', $visaProcess->candidate) }}" class="hover:text-blue-600">{{ $visaProcess->candidate->name }}</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $stage)) }}</span>
    </nav>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Candidate Info & Stage Navigation --}}
        <div class="lg:col-span-1">
            {{-- Candidate Card --}}
            <div class="bg-white rounded-lg shadow-sm border mb-4">
                <div class="bg-blue-600 text-white px-4 py-3 rounded-t-lg">
                    <h3 class="font-semibold"><i class="fas fa-user mr-2"></i>Candidate</h3>
                </div>
                <div class="p-4">
                    <h4 class="font-bold text-lg">{{ $visaProcess->candidate->name }}</h4>
                    <p class="text-sm text-gray-500 font-mono">{{ $visaProcess->candidate->btevta_id }}</p>
                    <div class="mt-3 space-y-1 text-sm">
                        <p><span class="text-gray-500">Trade:</span> {{ $visaProcess->candidate->trade->name ?? 'N/A' }}</p>
                        <p><span class="text-gray-500">Campus:</span> {{ $visaProcess->candidate->campus->name ?? 'N/A' }}</p>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('visa-processing.show', $visaProcess->candidate) }}" class="text-blue-600 hover:underline text-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Visa Process
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stage Navigation --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-4 py-3 border-b">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-list-ol mr-2"></i>Stages</h3>
                </div>
                <div class="divide-y">
                    @foreach($stagesOverview as $stageKey => $stageInfo)
                    <a href="{{ route('visa-processing.stage-details', [$visaProcess, $stageKey]) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors {{ $stageKey === $stage ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                        <i class="{{ $stageInfo['icon'] }} {{ $stageKey === $stage ? 'text-blue-600' : 'text-gray-400' }}"></i>
                        <div class="flex-1">
                            <span class="text-sm {{ $stageKey === $stage ? 'font-bold text-blue-700' : 'text-gray-700' }}">{{ $stageInfo['name'] }}</span>
                        </div>
                        @php
                            $stageResult = $stageInfo['details']->getResultEnum();
                        @endphp
                        @if($stageResult)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $stageResult === \App\Enums\VisaStageResult::PASS ? 'bg-green-100 text-green-800' :
                                   ($stageResult === \App\Enums\VisaStageResult::FAIL || $stageResult === \App\Enums\VisaStageResult::REFUSED ? 'bg-red-100 text-red-800' :
                                   ($stageResult === \App\Enums\VisaStageResult::SCHEDULED ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $stageResult->label() }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Pending</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: Stage Details & Actions --}}
        <div class="lg:col-span-2">
            {{-- Current Stage Header --}}
            <div class="bg-white rounded-lg shadow-sm border mb-4">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="{{ $stagesOverview[$stage]['icon'] ?? 'fas fa-cog' }} text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">{{ $stagesOverview[$stage]['name'] ?? ucfirst(str_replace('_', ' ', $stage)) }}</h2>
                                <p class="text-sm text-gray-500">Stage details and management</p>
                            </div>
                        </div>
                        @php
                            $currentResult = $details->getResultEnum();
                        @endphp
                        @if($currentResult)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium
                                {{ $currentResult === \App\Enums\VisaStageResult::PASS ? 'bg-green-100 text-green-800' :
                                   ($currentResult === \App\Enums\VisaStageResult::FAIL || $currentResult === \App\Enums\VisaStageResult::REFUSED ? 'bg-red-100 text-red-800' :
                                   ($currentResult === \App\Enums\VisaStageResult::SCHEDULED ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}">
                                <i class="{{ $currentResult->icon() }}"></i>
                                {{ $currentResult->label() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Current Details --}}
            @if($details->isScheduled() || $details->hasResult())
            <div class="bg-white rounded-lg shadow-sm border mb-4">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-info-circle mr-2"></i>Current Information</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($details->appointmentDate)
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Appointment Date</span>
                            <p class="font-medium">{{ $details->appointmentDate }}</p>
                        </div>
                        @endif
                        @if($details->appointmentTime)
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Appointment Time</span>
                            <p class="font-medium">{{ $details->appointmentTime }}</p>
                        </div>
                        @endif
                        @if($details->center)
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Center / Location</span>
                            <p class="font-medium">{{ $details->center }}</p>
                        </div>
                        @endif
                        @if($details->resultStatus)
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Result Status</span>
                            <p class="font-medium capitalize">{{ $details->resultStatus }}</p>
                        </div>
                        @endif
                        @if($details->notes)
                        <div class="md:col-span-2">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Notes</span>
                            <p class="font-medium">{{ $details->notes }}</p>
                        </div>
                        @endif
                        @if($details->hasEvidence())
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Evidence</span>
                            <p>
                                <a href="{{ route('secure-file.download', $details->evidencePath) }}" class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                                    <i class="fas fa-download"></i> Download Evidence
                                </a>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Action Forms --}}
            @can('update', $visaProcess)
            @if($stage !== 'visa_application')
            <div class="bg-white rounded-lg shadow-sm border" x-data="{ activeAction: '{{ $details->isScheduled() && !$details->hasResult() ? 'result' : 'schedule' }}' }">
                <div class="flex border-b">
                    <button @click="activeAction = 'schedule'"
                        :class="activeAction === 'schedule' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500'"
                        class="flex-1 px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                        <i class="fas fa-calendar-plus mr-1"></i> Schedule
                    </button>
                    <button @click="activeAction = 'result'"
                        :class="activeAction === 'result' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500'"
                        class="flex-1 px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                        <i class="fas fa-clipboard-check mr-1"></i> Record Result
                    </button>
                    <button @click="activeAction = 'evidence'"
                        :class="activeAction === 'evidence' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500'"
                        class="flex-1 px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                        <i class="fas fa-file-upload mr-1"></i> Upload Evidence
                    </button>
                </div>

                <div class="p-6">
                    {{-- Schedule Form --}}
                    <div x-show="activeAction === 'schedule'" x-cloak>
                        <form action="{{ route('visa-processing.update-stage', [$visaProcess, $stage]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="schedule">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Date</label>
                                    <input type="date" name="appointment_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required min="{{ date('Y-m-d') }}" value="{{ old('appointment_date', $details->appointmentDate) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Time</label>
                                    <input type="time" name="appointment_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required value="{{ old('appointment_time', $details->appointmentTime) }}">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Center / Location</label>
                                    <input type="text" name="center" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter test center or location" required value="{{ old('center', $details->center) }}">
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-calendar-check mr-1"></i> Schedule Appointment
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Result Form --}}
                    <div x-show="activeAction === 'result'" x-cloak>
                        <form action="{{ route('visa-processing.update-stage', [$visaProcess, $stage]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="action" value="result">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Result</label>
                                    <select name="result_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                        <option value="">Select result...</option>
                                        @foreach(\App\Enums\VisaStageResult::cases() as $result)
                                            @if($result !== \App\Enums\VisaStageResult::SCHEDULED)
                                            <option value="{{ $result->value }}">{{ $result->label() }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter any notes or remarks...">{{ old('notes') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Evidence (required for pass/fail)</label>
                                    <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Accepted: PDF, JPG, PNG (max 10MB)</p>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-save mr-1"></i> Record Result
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Evidence Upload Form --}}
                    <div x-show="activeAction === 'evidence'" x-cloak>
                        <form action="{{ route('visa-processing.update-stage', [$visaProcess, $stage]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="action" value="evidence">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Evidence Document</label>
                                <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <p class="text-xs text-gray-500 mt-1">Accepted: PDF, JPG, PNG (max 10MB)</p>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-upload mr-1"></i> Upload Evidence
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @else
            {{-- Visa Application Form --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-semibold text-gray-700"><i class="fas fa-passport mr-2"></i>Update Visa Application</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('visa-processing.update-visa-application', $visaProcess) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Application Status</label>
                                    <select name="application_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                        @foreach(\App\Enums\VisaApplicationStatus::cases() as $status)
                                        <option value="{{ $status->value }}" {{ $visaProcess->visa_application_status === $status ? 'selected' : '' }}>
                                            {{ $status->label() }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Issued Status</label>
                                    <select name="issued_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Select --</option>
                                        @foreach(\App\Enums\VisaIssuedStatus::cases() as $status)
                                        <option value="{{ $status->value }}" {{ $visaProcess->visa_issued_status === $status ? 'selected' : '' }}>
                                            {{ $status->label() }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter notes...">{{ old('notes') }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Evidence</label>
                                <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <p class="text-xs text-gray-500 mt-1">Accepted: PDF, JPG, PNG (max 10MB)</p>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-1"></i> Update Visa Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            @endcan
        </div>
    </div>
</div>
@endsection
