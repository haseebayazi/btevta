@extends('layouts.admin')

@section('title', 'Create Training Assessment')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Create Training Assessment</h1>
        <p class="text-gray-600 mt-1">
            Candidate: <span class="font-semibold">{{ $candidate->name }}</span> ({{ $candidate->btevta_id }})
        </p>
        @if($candidate->batch)
            <p class="text-sm text-gray-500 mt-1">
                Batch: {{ $candidate->batch->batch_code }} | Allocated Number: {{ $candidate->allocated_number }}
            </p>
        @endif
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Please correct the following errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Assessment Form -->
    <form action="{{ route('admin.training-assessments.store', $candidate) }}"
          method="POST"
          enctype="multipart/form-data"
          class="bg-white rounded-lg shadow-md p-6">
        @csrf

        <!-- Assessment Type Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Assessment Type</h2>

            <div class="space-y-3">
                @foreach(\App\Enums\AssessmentType::cases() as $type)
                    <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border-2 transition-colors hover:bg-gray-50
                                  {{ $type->value === 'final' ? 'border-green-300' : 'border-blue-300' }}">
                        <input type="radio"
                               name="assessment_type"
                               value="{{ $type->value }}"
                               {{ old('assessment_type') === $type->value ? 'checked' : '' }}
                               required
                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-700">{{ $type->label() }}</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                           {{ $type->value === 'interim' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ ucfirst($type->value) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">{{ $type->description() }}</p>
                        </div>
                    </label>
                @endforeach
            </div>

            <!-- Assessment History -->
            @if($existingAssessments->isNotEmpty())
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <h3 class="font-medium text-yellow-900 mb-2">Existing Assessments:</h3>
                    <ul class="text-sm text-yellow-800 space-y-1">
                        @foreach($existingAssessments as $existing)
                            <li>
                                {{ $existing->assessment_type_label }} -
                                Score: {{ $existing->score }}/{{ $existing->max_score }} ({{ $existing->percentage }}%) -
                                {{ $existing->assessed_at->format('d M Y') }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Score Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Assessment Scores</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="score" class="block text-gray-700 font-medium mb-2">
                        Score Obtained <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="score"
                           id="score"
                           min="0"
                           max="{{ old('max_score', 100) }}"
                           value="{{ old('score') }}"
                           required
                           oninput="calculatePercentage()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Enter the score achieved by the candidate</p>
                </div>

                <div>
                    <label for="max_score" class="block text-gray-700 font-medium mb-2">
                        Maximum Score <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="max_score"
                           id="max_score"
                           min="1"
                           value="{{ old('max_score', 100) }}"
                           required
                           oninput="calculatePercentage()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Total marks for this assessment</p>
                </div>
            </div>

            <!-- Percentage Display -->
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Percentage:</span>
                        <span id="percentageDisplay" class="text-2xl font-bold text-blue-600 ml-2">0%</span>
                    </div>
                    <div id="passFailBadge" class="hidden px-3 py-1 rounded-full text-sm font-medium">
                    </div>
                </div>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                    <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p class="text-xs text-gray-600 mt-2">
                    Passing percentage: {{ config('wasl.assessment.passing_percentage', 60) }}%
                </p>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Assessment Notes</h2>

            <div>
                <label for="notes" class="block text-gray-700 font-medium mb-2">
                    Assessor's Observations
                </label>
                <textarea name="notes"
                          id="notes"
                          rows="5"
                          placeholder="Enter detailed notes about the candidate's performance, strengths, areas for improvement..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Record observations, remarks, and feedback for the candidate
                </p>
            </div>
        </div>

        <!-- Evidence Upload Section -->
        <div class="mb-6 pb-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Supporting Evidence (Optional)</h2>

            <div>
                <label for="evidence_file" class="block text-gray-700 font-medium mb-2">
                    Upload Assessment Evidence
                </label>
                <input type="file"
                       name="evidence_file"
                       id="evidence_file"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    Optional: Upload scanned assessment papers, answer sheets, or other supporting documents
                    <br>Allowed: PDF, JPG, PNG (Max 5MB)
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.candidates.show', $candidate) }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Save Assessment
            </button>
        </div>
    </form>

    <!-- Assessment Guidelines -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Assessment Guidelines</h3>
        <ul class="list-disc list-inside space-y-2 text-gray-700 text-sm">
            <li><strong>Interim Assessment:</strong> Conducted mid-way through training to evaluate progress</li>
            <li><strong>Final Assessment:</strong> Conducted at the end of training to assess completion</li>
            <li>Passing percentage is {{ config('wasl.assessment.passing_percentage', 60) }}% or higher</li>
            <li>Both interim and final assessments are required for training completion</li>
            <li>Assessment evidence should be uploaded for record-keeping and audit purposes</li>
            <li>Once both assessments are completed, the training status will be automatically updated</li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
const passingPercentage = {{ config('wasl.assessment.passing_percentage', 60) }};

function calculatePercentage() {
    const score = parseFloat(document.getElementById('score').value) || 0;
    const maxScore = parseFloat(document.getElementById('max_score').value) || 1;

    if (maxScore > 0) {
        const percentage = (score / maxScore) * 100;
        const roundedPercentage = Math.round(percentage * 10) / 10;

        // Update display
        document.getElementById('percentageDisplay').textContent = roundedPercentage + '%';
        document.getElementById('progressBar').style.width = percentage + '%';

        // Update pass/fail badge
        const badge = document.getElementById('passFailBadge');
        if (score > 0) {
            badge.classList.remove('hidden');
            if (percentage >= passingPercentage) {
                badge.textContent = 'PASS';
                badge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800';
                document.getElementById('progressBar').className = 'bg-green-600 h-2 rounded-full transition-all duration-300';
            } else {
                badge.textContent = 'FAIL';
                badge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800';
                document.getElementById('progressBar').className = 'bg-red-600 h-2 rounded-full transition-all duration-300';
            }
        } else {
            badge.classList.add('hidden');
            document.getElementById('progressBar').className = 'bg-blue-600 h-2 rounded-full transition-all duration-300';
        }

        // Update max for score input
        document.getElementById('score').setAttribute('max', maxScore);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculatePercentage();
});
</script>
@endpush
@endsection
