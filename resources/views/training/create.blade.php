@extends('layouts.app')
@section('title', 'Add Candidates to Training')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Add Candidates to Training</h2>
            <p class="text-gray-600 mt-1">Select a batch and choose candidates to add to training</p>
        </div>
        <a href="{{ route('training.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Training
        </a>
    </div>

    <form method="POST" action="{{ route('training.store') }}" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <!-- Batch Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Training Batch <span class="text-red-600">*</span>
            </label>
            <select name="batch_id" id="batchSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                <option value="">Select a batch</option>
                @foreach($batches as $batch)
                <option value="{{ $batch->id }}">
                    {{ $batch->name }} - {{ $batch->trade->name ?? 'N/A' }}
                    ({{ $batch->candidates_count ?? 0 }}/{{ $batch->capacity }} candidates)
                </option>
                @endforeach
            </select>
            @error('batch_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Candidate Selection -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-700">
                    Select Candidates <span class="text-red-600">*</span>
                    <span id="selectedCount" class="text-blue-600 font-semibold ml-2">(0 selected)</span>
                </label>
                <div class="flex space-x-2">
                    <button type="button" onclick="selectAll()" class="text-xs text-blue-600 hover:text-blue-800">
                        Select All
                    </button>
                    <span class="text-gray-300">|</span>
                    <button type="button" onclick="deselectAll()" class="text-xs text-gray-600 hover:text-gray-800">
                        Deselect All
                    </button>
                </div>
            </div>

            <!-- Search Box -->
            <div class="mb-3">
                <input type="text"
                       id="candidateSearch"
                       placeholder="🔍 Search by name or ID..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Candidates List with Checkboxes -->
            <div id="candidatesList" class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">
                @if(isset($candidates) && $candidates->count() > 0)
                    @foreach($candidates as $candidate)
                    <label class="flex items-center p-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer candidate-item"
                           data-name="{{ strtolower($candidate->name) }}"
                           data-id="{{ strtolower($candidate->btevta_id ?? $candidate->application_id) }}">
                        <input type="checkbox"
                               name="candidate_ids[]"
                               value="{{ $candidate->id }}"
                               class="candidate-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $candidate->name }}</p>
                            <p class="text-xs text-gray-500">
                                ID: {{ $candidate->btevta_id ?? $candidate->application_id }}
                                @if($candidate->trade)
                                    • Trade: {{ $candidate->trade->name }}
                                @endif
                            </p>
                        </div>
                        <span class="text-xs text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                    </label>
                    @endforeach
                @else
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                        <p>No candidates available for training</p>
                        <p class="text-sm mt-1">Candidates must be in "registered" status</p>
                    </div>
                @endif
            </div>
            @error('candidate_ids')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                <i class="fas fa-info-circle"></i> Only showing candidates eligible for training (registered status)
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4 border-t">
            <a href="{{ route('training.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-user-plus mr-2"></i>
                Add to Training
            </button>
        </div>
    </form>
</div>

<script>
// Update selected count
function updateSelectedCount() {
    const count = document.querySelectorAll('.candidate-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = `(${count} selected)`;
}

// Select all candidates
function selectAll() {
    document.querySelectorAll('.candidate-checkbox:not([style*="display: none"])').forEach(cb => {
        cb.checked = true;
    });
    updateSelectedCount();
}

// Deselect all candidates
function deselectAll() {
    document.querySelectorAll('.candidate-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

// Search functionality
document.getElementById('candidateSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.candidate-item').forEach(item => {
        const name = item.dataset.name;
        const id = item.dataset.id;
        const matches = name.includes(searchTerm) || id.includes(searchTerm);
        item.style.display = matches ? 'flex' : 'none';
    });
});

// Listen for checkbox changes
document.querySelectorAll('.candidate-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

// Initialize count on page load
updateSelectedCount();
</script>
@endsection
