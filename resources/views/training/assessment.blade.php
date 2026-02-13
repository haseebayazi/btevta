@extends('layouts.app')

@section('title', 'Record Assessment - ' . $candidate->name)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.index') }}" class="hover:text-blue-600">Training</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.show', $candidate) }}" class="hover:text-blue-600">{{ $candidate->btevta_id }}</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Assessment</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Record Assessment</h2>
            <p class="text-gray-500 text-sm mt-1">{{ $candidate->name }} - {{ $candidate->trade->name ?? 'N/A' }}</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <a href="{{ route('training.show', $candidate) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm inline-flex items-center">
                <i class="fas fa-arrow-left mr-1"></i> Back to Details
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-start justify-between">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="text-red-600 hover:text-red-800 ml-4" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="bg-blue-600 text-white px-5 py-3">
                    <h5 class="font-semibold"><i class="fas fa-chart-line mr-2"></i>New Assessment</h5>
                </div>
                <div class="p-5">
                    <form action="{{ route('training.store-assessment', $candidate) }}" method="POST" id="assessmentForm">
                        @csrf

                        {{-- Assessment Type --}}
                        <div class="mb-4" x-data="{ type: '{{ old('assessment_type', 'midterm') }}' }">
                            <label class="font-bold text-sm text-gray-700 block mb-2">Assessment Type <span class="text-red-600">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                <label :class="type === 'midterm' ? 'bg-blue-600 text-white' : 'border border-blue-500 text-blue-600 hover:bg-blue-50'" class="cursor-pointer px-4 py-2 rounded-lg text-sm transition-colors">
                                    <input type="radio" name="assessment_type" value="midterm" x-model="type" class="sr-only">
                                    <i class="fas fa-book mr-1"></i> Midterm
                                </label>
                                <label :class="type === 'practical' ? 'bg-blue-600 text-white' : 'border border-blue-500 text-blue-600 hover:bg-blue-50'" class="cursor-pointer px-4 py-2 rounded-lg text-sm transition-colors">
                                    <input type="radio" name="assessment_type" value="practical" x-model="type" class="sr-only">
                                    <i class="fas fa-tools mr-1"></i> Practical
                                </label>
                                <label :class="type === 'final' ? 'bg-blue-600 text-white' : 'border border-blue-500 text-blue-600 hover:bg-blue-50'" class="cursor-pointer px-4 py-2 rounded-lg text-sm transition-colors">
                                    <input type="radio" name="assessment_type" value="final" x-model="type" class="sr-only">
                                    <i class="fas fa-graduation-cap mr-1"></i> Final Exam
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {{-- Assessment Date --}}
                            <div class="md:col-span-2 mb-4">
                                <label class="font-bold text-sm text-gray-700 block mb-1">Assessment Date <span class="text-red-600">*</span></label>
                                <input type="date" name="assessment_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('assessment_date') ring-2 ring-red-500 border-transparent @enderror"
                                       value="{{ old('assessment_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                                @error('assessment_date')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Total Marks --}}
                            <div class="md:col-span-1 mb-4">
                                <label class="font-bold text-sm text-gray-700 block mb-1">Total Marks <span class="text-red-600">*</span></label>
                                <input type="number" name="total_marks" id="totalMarks" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('total_marks') ring-2 ring-red-500 border-transparent @enderror"
                                       value="{{ old('total_marks', 100) }}" min="1" required>
                                @error('total_marks')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Obtained Marks --}}
                            <div class="md:col-span-1 mb-4">
                                <label class="font-bold text-sm text-gray-700 block mb-1">Obtained Marks <span class="text-red-600">*</span></label>
                                <input type="number" name="obtained_marks" id="obtainedMarks" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('obtained_marks') ring-2 ring-red-500 border-transparent @enderror"
                                       value="{{ old('obtained_marks') }}" min="0" required>
                                @error('obtained_marks')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Grade (Auto-calculated) --}}
                            <div class="mb-4">
                                <label class="font-bold text-sm text-gray-700 block mb-1">Grade <span class="text-red-600">*</span></label>
                                <select name="grade" id="gradeSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('grade') ring-2 ring-red-500 border-transparent @enderror" required>
                                    <option value="">Select Grade</option>
                                    <option value="A+" {{ old('grade') === 'A+' ? 'selected' : '' }}>A+ (90-100%)</option>
                                    <option value="A" {{ old('grade') === 'A' ? 'selected' : '' }}>A (80-89%)</option>
                                    <option value="B" {{ old('grade') === 'B' ? 'selected' : '' }}>B (70-79%)</option>
                                    <option value="C" {{ old('grade') === 'C' ? 'selected' : '' }}>C (60-69%)</option>
                                    <option value="D" {{ old('grade') === 'D' ? 'selected' : '' }}>D (50-59%)</option>
                                    <option value="F" {{ old('grade') === 'F' ? 'selected' : '' }}>F (Below 50%)</option>
                                </select>
                                @error('grade')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Result Display --}}
                            <div class="mb-4">
                                <label class="font-bold text-sm text-gray-700 block mb-1">Result</label>
                                <div class="flex items-center h-[38px]">
                                    <span id="resultBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">--</span>
                                    <span id="percentageDisplay" class="ml-2 text-gray-500 text-sm">--%</span>
                                </div>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="mb-4">
                            <label class="font-bold text-sm text-gray-700 block mb-1">Remarks</label>
                            <textarea name="remarks" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('remarks') ring-2 ring-red-500 border-transparent @enderror" rows="3"
                                      placeholder="Add remarks about the assessment...">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="flex justify-between pt-4 border-t border-gray-200">
                            <a href="{{ route('training.show', $candidate) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm inline-flex items-center">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium inline-flex items-center">
                                <i class="fas fa-save mr-1"></i> Save Assessment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Candidate Info --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-user mr-2"></i>Candidate Info</h5>
                </div>
                <div class="p-5 space-y-2 text-sm">
                    <p><strong class="text-gray-700">TheLeap ID:</strong> <span class="font-mono">{{ $candidate->btevta_id }}</span></p>
                    <p><strong class="text-gray-700">Name:</strong> {{ $candidate->name }}</p>
                    <p><strong class="text-gray-700">Trade:</strong> {{ $candidate->trade->name ?? 'N/A' }}</p>
                    <p><strong class="text-gray-700">Batch:</strong> {{ $candidate->batch->name ?? 'N/A' }}</p>
                </div>
            </div>

            {{-- Grading Guide --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-info-circle mr-2"></i>Grading Guide</h5>
                </div>
                <div class="p-5">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr><td class="py-1.5 text-gray-700">A+ (Excellent)</td><td class="py-1.5 text-right text-gray-600">90% - 100%</td></tr>
                            <tr><td class="py-1.5 text-gray-700">A (Very Good)</td><td class="py-1.5 text-right text-gray-600">80% - 89%</td></tr>
                            <tr><td class="py-1.5 text-gray-700">B (Good)</td><td class="py-1.5 text-right text-gray-600">70% - 79%</td></tr>
                            <tr><td class="py-1.5 text-gray-700">C (Average)</td><td class="py-1.5 text-right text-gray-600">60% - 69%</td></tr>
                            <tr><td class="py-1.5 text-gray-700">D (Pass)</td><td class="py-1.5 text-right text-gray-600">50% - 59%</td></tr>
                            <tr><td class="py-1.5 text-red-600">F (Fail)</td><td class="py-1.5 text-right text-red-600">Below 50%</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Previous Assessments --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-history mr-2"></i>Previous Assessments</h5>
                </div>
                <div class="p-5">
                    @if($candidate->assessments && $candidate->assessments->count() > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach($candidate->assessments->take(5) as $assessment)
                                <div class="py-3 first:pt-0 last:pb-0">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <strong class="text-sm text-gray-800">{{ ucfirst($assessment->assessment_type) }}</strong>
                                            <small class="text-gray-500 block text-xs">{{ $assessment->assessment_date ? $assessment->assessment_date->format('d M Y') : 'N/A' }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold text-sm text-gray-800">{{ $assessment->total_score }}/{{ $assessment->max_score }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium block mt-1 {{ $assessment->result === 'pass' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($assessment->result) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No previous assessments recorded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-calculate grade and result
    function calculateResult() {
        const totalMarks = parseFloat(document.getElementById('totalMarks').value) || 100;
        const obtainedMarks = parseFloat(document.getElementById('obtainedMarks').value) || 0;

        if (obtainedMarks > totalMarks) {
            document.getElementById('obtainedMarks').value = totalMarks;
            return;
        }

        const percentage = (obtainedMarks / totalMarks) * 100;
        let grade = 'F';
        let badgeClasses = 'bg-red-100 text-red-800';
        let resultText = 'Fail';

        if (percentage >= 90) {
            grade = 'A+'; badgeClasses = 'bg-green-100 text-green-800'; resultText = 'Excellent';
        } else if (percentage >= 80) {
            grade = 'A'; badgeClasses = 'bg-green-100 text-green-800'; resultText = 'Very Good';
        } else if (percentage >= 70) {
            grade = 'B'; badgeClasses = 'bg-blue-100 text-blue-800'; resultText = 'Good';
        } else if (percentage >= 60) {
            grade = 'C'; badgeClasses = 'bg-yellow-100 text-yellow-800'; resultText = 'Average';
        } else if (percentage >= 50) {
            grade = 'D'; badgeClasses = 'bg-yellow-100 text-yellow-800'; resultText = 'Pass';
        } else {
            grade = 'F'; badgeClasses = 'bg-red-100 text-red-800'; resultText = 'Fail';
        }

        // Update grade select
        document.getElementById('gradeSelect').value = grade;

        // Update result display
        const badge = document.getElementById('resultBadge');
        badge.textContent = resultText;
        badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ' + badgeClasses;
        document.getElementById('percentageDisplay').textContent = percentage.toFixed(1) + '%';
    }

    // Attach event listeners
    document.getElementById('totalMarks').addEventListener('input', calculateResult);
    document.getElementById('obtainedMarks').addEventListener('input', calculateResult);

    // Initial calculation if values exist
    document.addEventListener('DOMContentLoaded', calculateResult);
</script>
@endpush
@endsection
