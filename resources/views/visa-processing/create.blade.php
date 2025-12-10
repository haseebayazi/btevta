@extends('layouts.app')
@section('title', 'Start Visa Process')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Start Visa Processing</h2>
            <p class="text-gray-600 mt-1">Initiate visa application process for a candidate</p>
        </div>
        <a href="{{ route('visa-processing.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('visa-processing.store') }}" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-passport mr-2 text-blue-600"></i>
                Visa Application Details
            </h3>
        </div>

        <!-- Candidate Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Select Candidate <span class="text-red-600">*</span>
            </label>
            <select name="candidate_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('candidate_id') border-red-500 @enderror">
                <option value="">Choose a candidate...</option>
                @if(isset($candidate))
                    <option value="{{ $candidate->id }}" selected>
                        {{ $candidate->name }} ({{ $candidate->btevta_id }}) - {{ $candidate->trade->name ?? 'N/A' }}
                    </option>
                @else
                    @forelse($candidates ?? [] as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->name }} ({{ $c->btevta_id }})
                            @if($c->trade)
                                - {{ $c->trade->name }}
                            @endif
                        </option>
                    @empty
                        <option value="" disabled>No eligible candidates found</option>
                    @endforelse
                @endif
            </select>
            @error('candidate_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                <i class="fas fa-info-circle"></i>
                Only candidates who have completed training are shown
            </p>
        </div>

        <!-- Information Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-900 mb-2">
                <i class="fas fa-lightbulb mr-1"></i>
                What happens next?
            </h4>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>• A visa processing record will be created for this candidate</li>
                <li>• You'll be able to track application status and documents</li>
                <li>• Visa number and expiry date can be added once approved</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <a href="{{ route('visa-processing.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-play mr-2"></i>Start Visa Process
            </button>
        </div>
    </form>
</div>
@endsection
