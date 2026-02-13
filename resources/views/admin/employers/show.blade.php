@extends('layouts.admin')

@section('title', 'Employer Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Employer Details</h1>
                <p class="text-gray-600 mt-1">View complete employer information and employment package</p>
            </div>
            <div class="flex gap-3">
                @can('update', $employer)
                <a href="{{ route('admin.employers.edit', $employer) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Edit Employer
                </a>
                @endcan
                <a href="{{ route('admin.employers.index') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-6">
        @if($employer->is_active)
            <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                ✓ Active Employer
            </span>
        @else
            <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                ✗ Inactive Employer
            </span>
        @endif
    </div>

    <!-- Basic Information Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Basic Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Permission Number</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->permission_number }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Visa Issuing Company</label>
                <p class="text-gray-800 font-semibold text-lg">{{ $employer->visa_issuing_company }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Country</label>
                <p class="text-gray-800 font-semibold text-lg">
                    @if($employer->country)
                        {{ $employer->country->flag_emoji ? $employer->country->flag_emoji . ' ' : '' }}
                        {{ $employer->country->name }}
                    @else
                        <span class="text-gray-400">Not specified</span>
                    @endif
                </p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Sector</label>
                <p class="text-gray-800 font-semibold text-lg">
                    {{ $employer->sector ?? 'Not specified' }}
                </p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Trade/Occupation</label>
                <p class="text-gray-800 font-semibold text-lg">
                    {{ $employer->trade ?? 'Not specified' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Employment Package Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Employment Package</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Basic Salary</label>
                <p class="text-gray-800 font-semibold text-2xl">
                    @if($employer->basic_salary)
                        {{ number_format($employer->basic_salary, 2) }}
                        <span class="text-lg text-gray-600">{{ $employer->salary_currency ?? 'PKR' }}</span>
                    @else
                        <span class="text-gray-400 text-lg">Not specified</span>
                    @endif
                </p>
                <p class="text-xs text-gray-500 mt-1">Monthly basic salary</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Benefits Provided</label>
                <div class="space-y-2 mt-2">
                    <div class="flex items-center gap-2">
                        @if($employer->food_by_company)
                            <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm">✓</span>
                            <span class="text-gray-800">Food</span>
                        @else
                            <span class="w-6 h-6 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center text-sm">✗</span>
                            <span class="text-gray-400">Food</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($employer->accommodation_by_company)
                            <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm">✓</span>
                            <span class="text-gray-800">Accommodation</span>
                        @else
                            <span class="w-6 h-6 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center text-sm">✗</span>
                            <span class="text-gray-400">Accommodation</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($employer->transport_by_company)
                            <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm">✓</span>
                            <span class="text-gray-800">Transport</span>
                        @else
                            <span class="w-6 h-6 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center text-sm">✗</span>
                            <span class="text-gray-400">Transport</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($employer->other_conditions)
        <div>
            <label class="block text-gray-500 text-sm font-medium mb-2">Other Conditions & Terms</label>
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                <p class="text-gray-800 whitespace-pre-line">{{ $employer->other_conditions }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Supporting Documentation Section -->
    @if($employer->evidence_path)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Supporting Documentation</h2>

        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-md p-4">
            <div>
                <p class="text-gray-800 font-medium">{{ basename($employer->evidence_path) }}</p>
                <p class="text-sm text-gray-600 mt-1">Uploaded: {{ $employer->updated_at->format('M d, Y h:i A') }}</p>
            </div>
            <a href="{{ route('admin.employers.download-evidence', $employer) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Download
            </a>
        </div>
    </div>
    @endif

    <!-- Linked Candidates Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Linked Candidates</h2>

        @if($employer->currentCandidates()->count() > 0)
            <div class="mb-4">
                <p class="text-gray-700">
                    <strong>{{ $employer->currentCandidates()->count() }}</strong> candidate(s) currently linked to this employer
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($employer->currentCandidates as $candidate)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $candidate->candidate_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $candidate->full_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                    {{ $candidate->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $candidate->pivot->assigned_at ? \Carbon\Carbon::parse($candidate->pivot->assigned_at)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('candidates.show', $candidate) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-md p-6 text-center">
                <p class="text-gray-600">No candidates currently linked to this employer</p>
                <p class="text-sm text-gray-500 mt-2">Candidates are linked during the registration or post-departure process</p>
            </div>
        @endif
    </div>

    <!-- Record Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Record Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Created</label>
                <p class="text-gray-800">{{ $employer->created_at->format('M d, Y h:i A') }}</p>
                <p class="text-sm text-gray-500">{{ $employer->created_at->diffForHumans() }}</p>
            </div>

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Last Updated</label>
                <p class="text-gray-800">{{ $employer->updated_at->format('M d, Y h:i A') }}</p>
                <p class="text-sm text-gray-500">{{ $employer->updated_at->diffForHumans() }}</p>
            </div>

            @if($employer->creator)
            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Created By</label>
                <p class="text-gray-800">{{ $employer->creator->name }}</p>
                <p class="text-sm text-gray-500">{{ $employer->creator->email }}</p>
            </div>
            @endif

            <div>
                <label class="block text-gray-500 text-sm font-medium mb-1">Employer ID</label>
                <p class="text-gray-800 font-mono">{{ $employer->id }}</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
        <div class="flex gap-3">
            @can('delete', $employer)
            <form action="{{ route('admin.employers.destroy', $employer) }}"
                  method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this employer? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    Delete Employer
                </button>
            </form>
            @endcan
        </div>

        <div class="flex gap-3">
            @can('update', $employer)
            <a href="{{ route('admin.employers.edit', $employer) }}"
               class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Edit Employer
            </a>
            @endcan
            <a href="{{ route('admin.employers.index') }}"
               class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to List
            </a>
        </div>
    </div>
</div>
@endsection
