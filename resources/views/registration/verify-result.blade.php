@extends('layouts.app')

@section('title', 'Registration Verification')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white shadow-lg rounded-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Registration Verification</h2>
                <p class="text-sm text-gray-600 mt-2">BTEVTA Overseas Employment Program</p>
            </div>

            @if($success)
                <!-- Success State -->
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h3 class="text-lg font-semibold text-green-800 mb-2">
                        {{ $registration_complete ? 'Verified' : 'Pending Verification' }}
                    </h3>
                    <p class="text-gray-600 text-center mb-6">{{ $message }}</p>

                    <!-- Candidate Details -->
                    <div class="w-full bg-gray-50 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Name:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $candidate['name'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">BTEVTA ID:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $candidate['btevta_id'] ?? 'Pending' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="text-sm font-semibold px-2 py-1 rounded
                                @if($candidate['status'] == 'registered') bg-green-100 text-green-800
                                @elseif($candidate['status'] == 'training') bg-blue-100 text-blue-800
                                @elseif($candidate['status'] == 'departed') bg-purple-100 text-purple-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $candidate['status'])) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Trade:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $candidate['trade'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Campus:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $candidate['campus'] }}</span>
                        </div>
                        @if($candidate['registration_date'])
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Registered On:</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $candidate['registration_date'] }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Error State -->
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>

                    <h3 class="text-lg font-semibold text-red-800 mb-2">Verification Failed</h3>
                    <p class="text-gray-600 text-center">{{ $message }}</p>
                </div>
            @endif

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
                    This verification was performed on {{ now()->format('d M, Y \a\t H:i') }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    BTEVTA Overseas Employment Management System
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
