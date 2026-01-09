@extends('layouts.app')

@section('title', 'Registration Status')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Registration Status</h1>
            <p class="text-gray-600 mt-1">{{ $candidate->name }} ({{ $candidate->btevta_id }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('registration.show', $candidate) }}" class="btn btn-info">
                <i class="fas fa-eye mr-2"></i>Full Details
            </a>
            <a href="{{ route('registration.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Status -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Overall Progress -->
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">
                        <i class="fas fa-tasks mr-2 text-blue-500"></i>Registration Progress
                    </h2>
                    @if($status['can_complete'])
                        <span class="badge badge-success">Ready to Complete</span>
                    @else
                        <span class="badge badge-warning">In Progress</span>
                    @endif
                </div>

                <!-- Progress Bar -->
                @php
                    $completedSteps = 0;
                    $totalSteps = 3;
                    if($status['documents_complete']) $completedSteps++;
                    if($status['next_of_kin']) $completedSteps++;
                    if($status['undertaking']) $completedSteps++;
                    $progressPercent = ($completedSteps / $totalSteps) * 100;
                @endphp

                <div class="mb-6">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                        <span class="text-sm font-medium text-gray-700">{{ $completedSteps }}/{{ $totalSteps }} Complete</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-gradient-to-r from-blue-500 to-green-500 h-4 rounded-full transition-all"
                             style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>

                <!-- Steps -->
                <div class="space-y-4">
                    <!-- Documents Step -->
                    <div class="flex items-start gap-4 p-4 rounded-lg {{ $status['documents_complete'] ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $status['documents_complete'] ? 'bg-green-500' : 'bg-yellow-500' }}">
                            <i class="fas {{ $status['documents_complete'] ? 'fa-check' : 'fa-file-alt' }} text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold {{ $status['documents_complete'] ? 'text-green-900' : 'text-yellow-900' }}">
                                Required Documents
                            </h3>
                            <p class="text-sm {{ $status['documents_complete'] ? 'text-green-700' : 'text-yellow-700' }}">
                                @if($status['documents_complete'])
                                    All required documents uploaded and valid
                                @else
                                    Some documents are missing or expired
                                @endif
                            </p>
                        </div>
                        @if($status['documents_complete'])
                            <span class="badge badge-success">Complete</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </div>

                    <!-- Next of Kin Step -->
                    <div class="flex items-start gap-4 p-4 rounded-lg {{ $status['next_of_kin'] ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $status['next_of_kin'] ? 'bg-green-500' : 'bg-yellow-500' }}">
                            <i class="fas {{ $status['next_of_kin'] ? 'fa-check' : 'fa-user-friends' }} text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold {{ $status['next_of_kin'] ? 'text-green-900' : 'text-yellow-900' }}">
                                Next of Kin Information
                            </h3>
                            <p class="text-sm {{ $status['next_of_kin'] ? 'text-green-700' : 'text-yellow-700' }}">
                                @if($status['next_of_kin'])
                                    Emergency contact information provided
                                @else
                                    Please provide next of kin details
                                @endif
                            </p>
                        </div>
                        @if($status['next_of_kin'])
                            <span class="badge badge-success">Complete</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </div>

                    <!-- Undertaking Step -->
                    <div class="flex items-start gap-4 p-4 rounded-lg {{ $status['undertaking'] ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $status['undertaking'] ? 'bg-green-500' : 'bg-yellow-500' }}">
                            <i class="fas {{ $status['undertaking'] ? 'fa-check' : 'fa-signature' }} text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold {{ $status['undertaking'] ? 'text-green-900' : 'text-yellow-900' }}">
                                Undertaking/Declaration
                            </h3>
                            <p class="text-sm {{ $status['undertaking'] ? 'text-green-700' : 'text-yellow-700' }}">
                                @if($status['undertaking'])
                                    Undertaking signed and submitted
                                @else
                                    Please sign the required undertaking
                                @endif
                            </p>
                        </div>
                        @if($status['undertaking'])
                            <span class="badge badge-success">Complete</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Document Details -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-file-alt mr-2 text-purple-500"></i>Document Checklist
                </h2>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>Status</th>
                                <th>Expiry</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($status['documents'] as $type => $doc)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if($doc['uploaded'] && !$doc['expired'])
                                            <i class="fas fa-check-circle text-green-500"></i>
                                        @elseif($doc['expired'])
                                            <i class="fas fa-exclamation-circle text-red-500"></i>
                                        @else
                                            <i class="fas fa-times-circle text-gray-400"></i>
                                        @endif
                                        <span class="font-medium">{{ $doc['label'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($doc['uploaded'])
                                        <span class="badge badge-{{ $doc['status'] == 'verified' ? 'success' : ($doc['status'] == 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($doc['status']) }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Not Uploaded</span>
                                    @endif
                                </td>
                                <td>
                                    @if($doc['expiry_date'])
                                        <span class="{{ $doc['expired'] ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                            {{ $doc['expiry_date'] }}
                                        </span>
                                        @if($doc['expired'])
                                            <span class="text-red-500 text-sm block">Expired</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$doc['uploaded'])
                                        <span class="text-yellow-600 text-sm">Required</span>
                                    @elseif($doc['expired'])
                                        <span class="text-red-600 text-sm">Please re-upload valid document</span>
                                    @else
                                        <span class="text-green-600 text-sm">OK</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Candidate Card -->
            <div class="card text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user text-blue-600 text-3xl"></i>
                </div>
                <h3 class="text-lg font-semibold">{{ $candidate->name }}</h3>
                <p class="text-gray-600 font-mono">{{ $candidate->btevta_id }}</p>
                <div class="mt-3">
                    <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</span>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2 text-gray-500"></i>Candidate Info
                </h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Trade</span>
                        <span class="font-medium">{{ $candidate->trade->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Campus</span>
                        <span class="font-medium">{{ $candidate->campus->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Current Status</span>
                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-cogs mr-2 text-gray-500"></i>Actions
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('registration.show', $candidate) }}" class="btn btn-info w-full justify-center">
                        <i class="fas fa-edit mr-2"></i>Edit Registration
                    </a>

                    @if($status['can_complete'])
                        <form action="{{ route('registration.complete', $candidate) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-full justify-center"
                                    onclick="return confirm('Complete registration for this candidate?')">
                                <i class="fas fa-check-circle mr-2"></i>Complete Registration
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-secondary w-full justify-center cursor-not-allowed" disabled>
                            <i class="fas fa-ban mr-2"></i>Cannot Complete Yet
                        </button>
                        <p class="text-sm text-gray-500 text-center">Complete all required steps first</p>
                    @endif
                </div>
            </div>

            <!-- Help -->
            <div class="card bg-blue-50 border border-blue-200">
                <h2 class="text-sm font-semibold text-blue-900 mb-2">
                    <i class="fas fa-question-circle mr-1"></i>Need Help?
                </h2>
                <p class="text-sm text-blue-800">
                    If you're having trouble completing the registration, please ensure all documents are valid and not expired. Contact support if issues persist.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
