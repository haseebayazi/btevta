@extends('layouts.app')

@section('title', 'Employer Candidates - ' . $employer->visa_issuing_company)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Candidates for {{ $employer->visa_issuing_company }}</h1>
                <p class="text-gray-600 mt-1">
                    {{ $employer->country?->name ?? '' }}
                    {{ $employer->sector ? '- ' . $employer->sector : '' }}
                    | {{ $candidates->count() }} candidate(s)
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.employers.show', $employer) }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left"></i> Back to Employer
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Candidates Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($candidates->count() > 0)
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trade</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employment Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignment Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($candidates as $candidate)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $candidate->candidate_id ?? $candidate->id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $candidate->full_name ?? $candidate->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $candidate->campus?->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $candidate->trade?->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">{{ $candidate->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($candidate->pivot->employment_type)
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                                {{ ucfirst($candidate->pivot->employment_type) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @php $pivotStatus = $candidate->pivot->status ?? 'pending'; @endphp
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $pivotStatus === 'active' ? 'bg-green-100 text-green-800' :
                               ($pivotStatus === 'completed' ? 'bg-gray-100 text-gray-800' :
                               ($pivotStatus === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                            {{ ucfirst($pivotStatus) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $candidate->pivot->assignment_date ? \Carbon\Carbon::parse($candidate->pivot->assignment_date)->format('M d, Y') : ($candidate->pivot->assigned_at ? \Carbon\Carbon::parse($candidate->pivot->assigned_at)->format('M d, Y') : 'N/A') }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('candidates.show', $candidate) }}" class="text-blue-600 hover:text-blue-900">
                            View Profile
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-6 text-center text-gray-500">
            No candidates linked to this employer.
        </div>
        @endif
    </div>
</div>
@endsection
