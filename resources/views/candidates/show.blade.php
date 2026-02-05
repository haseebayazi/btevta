@extends('layouts.app')

@section('title', 'View Candidate - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $candidate->name }}</h1>
                <p class="text-gray-600 mt-2">TheLeap ID: <span class="font-semibold">{{ $candidate->btevta_id }}</span></p>
            </div>
            <div class="flex gap-2">
                @can('view', $candidate)
                <a href="{{ route('candidates.journey', $candidate) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-map-marked-alt mr-2"></i>Journey
                </a>
                @endcan
                @can('update', $candidate)
                <a href="{{ route('candidates.edit', $candidate) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                @endcan
                <a href="{{ route('candidates.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Current Status</h2>
                        <p class="text-gray-600 text-sm mt-1">Application ID: {{ $candidate->application_id }}</p>
                    </div>
                    @php
                        $statusEnum = \App\Enums\CandidateStatus::tryFrom($candidate->status);
                        $statusColor = $statusEnum ? $statusEnum->color() : 'secondary';
                        $statusLabel = $statusEnum ? $statusEnum->label() : ucfirst($candidate->status);
                    @endphp
                    <span class="inline-block px-4 py-2 rounded-full text-white bg-{{ $statusColor }}-500">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 pb-4 border-b">Personal Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Full Name</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Father's Name</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->father_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">CNIC</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->cnic }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date of Birth</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->date_of_birth?->format('d M, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Age</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->age ?? 'N/A' }} years</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Gender</p>
                        <p class="text-lg font-semibold text-gray-900 capitalize">{{ $candidate->gender }}</p>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 pb-4 border-b">Contact Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Phone</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->phone }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-600">Address</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->full_address ?? $candidate->address }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">District</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->district }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tehsil</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->tehsil ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Training Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 pb-4 border-b">Training Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Campus</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->campus?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Trade</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->trade?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Batch</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->batch?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">OEP</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $candidate->oep?->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            @if($candidate->remarks)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-4 border-b">Remarks</h2>
                <p class="text-gray-700">{{ $candidate->remarks }}</p>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Photo Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Candidate Photo</h3>
                @if($candidate->photo_url)
                    <img src="{{ $candidate->photo_url }}" alt="{{ $candidate->name }}" class="w-full h-auto rounded-lg border border-gray-300" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-full aspect-square bg-gray-200 rounded-lg items-center justify-center" style="display: none;">
                        <div class="text-center">
                            <i class="fas fa-user text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500">Photo unavailable</p>
                        </div>
                    </div>
                @else
                    <div class="w-full aspect-square bg-gray-200 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-user text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500">No photo</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Info Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Info</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-semibold">{{ $candidate->created_at?->format('d M, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-semibold">{{ $candidate->updated_at?->format('d M, Y') }}</span>
                    </div>
                    @if($candidate->user)
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Created By:</span>
                        <span class="font-semibold">{{ $candidate->user->name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Record ID:</span>
                        <span class="font-semibold text-sm">{{ $candidate->id }}</span>
                    </div>
                </div>
            </div>

            {{-- Pre-Departure Documents Card - Always visible for document management --}}
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pre-Departure Documents</h3>
                @php
                    $docStatus = $candidate->getPreDepartureDocumentStatus();
                @endphp
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Mandatory Documents</span>
                        <span class="{{ $docStatus['is_complete'] ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $docStatus['mandatory_uploaded'] }}/{{ $docStatus['mandatory_total'] }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $docStatus['is_complete'] ? 'bg-green-500' : 'bg-yellow-500' }}"
                             style="width: {{ $docStatus['completion_percentage'] }}%"></div>
                    </div>
                </div>
                @can('viewAny', [App\Models\PreDepartureDocument::class, $candidate])
                <a href="{{ route('candidates.pre-departure-documents.index', $candidate) }}"
                   class="block w-full {{ $docStatus['is_complete'] ? 'bg-blue-600 hover:bg-blue-700' : 'bg-yellow-500 hover:bg-yellow-600' }} text-white text-center px-4 py-2 rounded-lg transition">
                    <i class="fas fa-file-alt mr-2"></i>
                    {{ $docStatus['is_complete'] ? 'View Documents' : 'Upload Documents' }}
                </a>
                @endcan
            </div>

            {{-- Initial Screening Card - Module 2 Entry Point --}}
            @if(in_array($candidate->status, ['listed', 'pre_departure_docs', 'new', 'screening']))
            @php
                $docStatus = $candidate->getPreDepartureDocumentStatus();
                $readyForScreening = $candidate->hasCompletedAndVerifiedPreDepartureDocuments();
                $transitionCheck = $candidate->canTransitionToScreening();
            @endphp
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Initial Screening</h3>
                @if($readyForScreening)
                    <div class="mb-4">
                        <div class="flex items-center text-sm text-green-600 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>
                            All documents verified
                        </div>
                        <p class="text-sm text-gray-600">Candidate is ready for Module 2 Initial Screening.</p>
                    </div>
                    @can('create', App\Models\CandidateScreening::class)
                    <a href="{{ route('candidates.initial-screening', $candidate) }}"
                       class="block w-full bg-green-600 hover:bg-green-700 text-white text-center px-4 py-2 rounded-lg transition">
                        <i class="fas fa-clipboard-check mr-2"></i>Start Initial Screening
                    </a>
                    @endcan
                @else
                    <div class="mb-4">
                        <div class="flex items-center text-sm text-yellow-600 mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Not ready for screening
                        </div>
                        @if(!empty($transitionCheck['issues']))
                            <ul class="text-sm text-gray-600 list-disc list-inside">
                                @foreach($transitionCheck['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <a href="{{ route('candidates.pre-departure-documents.index', $candidate) }}"
                       class="block w-full bg-yellow-500 hover:bg-yellow-600 text-white text-center px-4 py-2 rounded-lg transition">
                        <i class="fas fa-file-upload mr-2"></i>Complete Documents First
                    </a>
                @endif
            </div>
            @endif

            {{-- Registration Card - Module 3 Entry Point for Screened Candidates --}}
            @if($candidate->status === 'screened')
            <div class="bg-white rounded-lg shadow-md p-6 border-2 border-green-500">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Ready for Registration
                </h3>
                <div class="mb-4">
                    <div class="flex items-center text-sm text-green-600 mb-2">
                        <i class="fas fa-clipboard-check mr-2"></i>
                        Screening completed successfully
                    </div>
                    <p class="text-sm text-gray-600">This candidate has passed screening and is ready for Module 3 Registration with campus allocation, course assignment, and NOK financial details.</p>
                </div>
                @can('update', $candidate)
                <a href="{{ route('registration.allocation', $candidate) }}"
                   class="block w-full bg-green-600 hover:bg-green-700 text-white text-center px-4 py-2 rounded-lg transition">
                    <i class="fas fa-user-plus mr-2"></i>Proceed to Registration
                </a>
                @endcan
            </div>
            @endif

            <!-- Action Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-2">
                    @can('update', $candidate)
                    <a href="{{ route('candidates.edit', $candidate) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-2 rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>Edit Candidate
                    </a>
                    @endcan
                    @can('delete', $candidate)
                    <form action="{{ route('candidates.destroy', $candidate) }}" method="POST" class="inline-block w-full" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full bg-red-600 hover:bg-red-700 text-white text-center px-4 py-2 rounded-lg transition">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
