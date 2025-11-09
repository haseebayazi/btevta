@extends('layouts.app')

@section('title', 'View Candidate - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $candidate->name }}</h1>
                <p class="text-gray-600 mt-2">BTEVTA ID: <span class="font-semibold">{{ $candidate->btevta_id }}</span></p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('candidates.edit', $candidate) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
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
                    <span class="inline-block px-4 py-2 rounded-full text-white 
                        @if($candidate->status == 'active') bg-green-500
                        @elseif($candidate->status == 'pending') bg-yellow-500
                        @elseif($candidate->status == 'completed') bg-blue-500
                        @else bg-red-500 @endif">
                        {{ ucfirst($candidate->status) }}
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
                @if($candidate->photo_path)
                    <img src="{{ Storage::url($candidate->photo_path) }}" alt="{{ $candidate->name }}" class="w-full h-auto rounded-lg border border-gray-300">
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

            <!-- Action Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('candidates.edit', $candidate) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-2 rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>Edit Candidate
                    </a>
                    <button type="button" onclick="if(confirm('Are you sure?')) deleteCandidate({{ $candidate->id }})" class="block w-full bg-red-600 hover:bg-red-700 text-white text-center px-4 py-2 rounded-lg transition">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCandidate(id) {
    // Implementation for delete would go here
    // This would typically send a DELETE request to the server
    alert('Delete functionality would be implemented here');
}
</script>
@endsection
