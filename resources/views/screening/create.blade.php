@extends('layouts.app')
@section('title', 'Log Screening Call')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Log Screening Call</h2>
            <p class="text-gray-600 mt-1">Record a new candidate screening call</p>
        </div>
        <a href="{{ route('screening.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <form action="{{ route('screening.store') }}" method="POST" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-phone mr-2 text-blue-600"></i>
                Screening Information
            </h3>
        </div>

        <!-- Candidate Selection -->
        <div>
            <label for="candidate_id" class="block text-sm font-medium text-gray-700 mb-2">
                Candidate <span class="text-red-600">*</span>
            </label>
            <select name="candidate_id" id="candidate_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('candidate_id') border-red-500 @enderror">
                <option value="">Select Candidate</option>
                @foreach($candidates as $candidate)
                    <option value="{{ $candidate->id }}" {{ request('candidate_id') == $candidate->id ? 'selected' : '' }}>
                        {{ $candidate->name }} ({{ $candidate->btevta_id }})
                    </option>
                @endforeach
            </select>
            @error('candidate_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Select the candidate being screened</p>
        </div>

        <!-- Date and Duration Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="screening_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Screening Date & Time <span class="text-red-600">*</span>
                </label>
                <input type="datetime-local" name="screening_date" id="screening_date" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('screening_date') border-red-500 @enderror"
                       value="{{ old('screening_date', now()->format('Y-m-d\TH:i')) }}">
                @error('screening_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="call_duration" class="block text-sm font-medium text-gray-700 mb-2">
                    Call Duration (minutes) <span class="text-red-600">*</span>
                </label>
                <input type="number" name="call_duration" id="call_duration" min="1" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('call_duration') border-red-500 @enderror"
                       value="{{ old('call_duration') }}" placeholder="e.g., 15">
                @error('call_duration')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Screening Outcome -->
        <div>
            <label for="screening_outcome" class="block text-sm font-medium text-gray-700 mb-2">
                Screening Outcome <span class="text-red-600">*</span>
            </label>
            <select name="screening_outcome" id="screening_outcome" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('screening_outcome') border-red-500 @enderror">
                <option value="">Select Outcome</option>
                <option value="pending" {{ old('screening_outcome') === 'pending' ? 'selected' : '' }}>
                    ⏳ Pending
                </option>
                <option value="pass" {{ old('screening_outcome') === 'pass' ? 'selected' : '' }}>
                    ✅ Pass
                </option>
                <option value="fail" {{ old('screening_outcome') === 'fail' ? 'selected' : '' }}>
                    ❌ Fail
                </option>
            </select>
            @error('screening_outcome')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Call Notes -->
        <div>
            <label for="call_notes" class="block text-sm font-medium text-gray-700 mb-2">
                Call Notes
            </label>
            <textarea name="call_notes" id="call_notes" rows="4"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('call_notes') border-red-500 @enderror"
                      placeholder="Enter notes from the screening call...">{{ old('call_notes') }}</textarea>
            @error('call_notes')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remarks -->
        <div>
            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
                Remarks
            </label>
            <textarea name="remarks" id="remarks" rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('remarks') border-red-500 @enderror"
                      placeholder="Additional remarks or observations...">{{ old('remarks') }}</textarea>
            @error('remarks')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <a href="{{ route('screening.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>Save Screening Record
            </button>
        </div>
    </form>
</div>
@endsection
