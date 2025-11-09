@extends('layouts.app')

@section('title', 'Assessment - ' . $training->title)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-3xl font-bold">Training Assessment</h1>
            <p class="text-gray-600 mt-1">{{ $training->title }} - Batch: {{ $training->batch_name }}</p>
        </div>
        <a href="{{ route('training.show', $training) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card">
                <form method="POST" action="{{ route('training.assessment.store', $training) }}" id="assessmentForm">
                    @csrf
                    
                    <!-- Assessment Type Tabs -->
                    <div class="mb-6">
                        <div class="flex bg-gray-100 p-1 rounded-lg">
                            <button type="button" class="tab-btn active" data-type="theory">
                                <i class="fas fa-book mr-2"></i>Theory
                            </button>
                            <button type="button" class="tab-btn" data-type="practical">
                                <i class="fas fa-tools mr-2"></i>Practical
                            </button>
                            <button type="button" class="tab-btn" data-type="final">
                                <i class="fas fa-graduation-cap mr-2"></i>Final Exam
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="assessment_type" id="assessment_type" value="theory">

                    <!-- Assessment Details -->
                    <div class="grid md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="form-label required">Assessment Date</label>
                            <input type="date" name="assessment_date" value="{{ old('assessment_date', date('Y-m-d')) }}" 
                                   class="form-input" required max="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="form-label required">Maximum Marks</label>
                            <input type="number" name="max_marks" value="{{ old('max_marks', 100) }}" 
                                   class="form-input" required min="1" id="maxMarks">
                        </div>
                        <div>
                            <label class="form-label required">Passing Marks</label>
                            <input type="number" name="passing_marks" value="{{ old('passing_marks', 40) }}" 
                                   class="form-input" required min="1" id="passingMarks">
                        </div>
                        <div>
                            <label class="form-label">Assessor Name</label>
                            <input type="text" name="assessor_name" value="{{ old('assessor_name', auth()->user()->name) }}" 
                                   class="form-input">
                        </div>
                    </div>

                    <!-- Candidates Marks Entry -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Enter Marks for Each Candidate</h3>
                        <div class="space-y-3">
                            @forelse($candidates as $candidate)
                            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                <div class="flex items-center space-x-4 flex-1">
                                    <img src="{{ $candidate->photo_url ?? asset('img/default.png') }}" 
                                         class="w-12 h-12 rounded-full">
                                    <div>
                                        <h4 class="font-semibold">{{ $candidate->name }}</h4>
                                        <p class="text-sm text-gray-600">{{ $candidate->passport_number }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <input type="number" name="marks[{{ $candidate->id }}]" 
                                           class="marks-input w-28 px-3 py-2 border rounded text-center" 
                                           placeholder="0" min="0" step="0.5" 
                                           data-candidate-id="{{ $candidate->id }}">
                                    <span class="grade-badge w-20 text-center font-semibold" 
                                          id="grade-{{ $candidate->id }}">-</span>
                                    <span class="status-badge w-24 text-center text-xs font-medium" 
                                          id="status-{{ $candidate->id }}"></span>
                                </div>
                            </div>
                            @empty
                            <p class="text-center text-gray-500 py-8">No candidates enrolled</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="mt-6">
                        <label class="form-label">Assessment Remarks</label>
                        <textarea name="remarks" rows="3" class="form-input" 
                                  placeholder="Add remarks about the assessment...">{{ old('remarks') }}</textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <a href="{{ route('training.show', $training) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Save Assessment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grading System -->
        <div class="lg:col-span-1">
            <div class="card mb-4">
                <h3 class="text-lg font-semibold mb-4">Grading System</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>A+ (90-100%)</span>
                        <span class="badge badge-success">Excellent</span>
                    </div>
                    <div class="flex justify-between">
                        <span>A (80-89%)</span>
                        <span class="badge badge-info">Very Good</span>
                    </div>
                    <div class="flex justify-between">
                        <span>B+ (70-79%)</span>
                        <span class="badge badge-primary">Good</span>
                    </div>
                    <div class="flex justify-between">
                        <span>B (60-69%)</span>
                        <span class="badge badge-warning">Above Average</span>
                    </div>
                    <div class="flex justify-between">
                        <span>C (50-59%)</span>
                        <span class="badge badge-secondary">Average</span>
                    </div>
                    <div class="flex justify-between">
                        <span>D (40-49%)</span>
                        <span class="badge badge-danger">Below Average</span>
                    </div>
                    <div class="flex justify-between">
                        <span>F (<40%)</span>
                        <span class="badge badge-dark">Fail</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="text-lg font-semibold mb-3">Quick Stats</h3>
                <div class="text-sm text-gray-600">
                    <p class="mb-2">Total Candidates: <span class="font-bold">{{ $candidates->count() }}</span></p>
                    <p class="mb-2">Max Marks: <span class="font-bold" id="displayMaxMarks">100</span></p>
                    <p class="mb-2">Passing Marks: <span class="font-bold" id="displayPassingMarks">40</span></p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('assessment_type').value = this.dataset.type;
    });
});

// Grade calculation
function calculateGrade(marks, maxMarks) {
    const percentage = (marks / maxMarks) * 100;
    if (percentage >= 90) return {grade: 'A+', class: 'success', status: 'Excellent'};
    if (percentage >= 80) return {grade: 'A', class: 'info', status: 'Very Good'};
    if (percentage >= 70) return {grade: 'B+', class: 'primary', status: 'Good'};
    if (percentage >= 60) return {grade: 'B', class: 'warning', status: 'Above Avg'};
    if (percentage >= 50) return {grade: 'C', class: 'secondary', status: 'Average'};
    if (percentage >= 40) return {grade: 'D', class: 'danger', status: 'Below Avg'};
    return {grade: 'F', class: 'dark', status: 'Fail'};
}

// Real-time grade update
document.querySelectorAll('.marks-input').forEach(input => {
    input.addEventListener('input', function() {
        const candidateId = this.dataset.candidateId;
        const marks = parseFloat(this.value) || 0;
        const maxMarks = parseFloat(document.getElementById('maxMarks').value) || 100;
        const passingMarks = parseFloat(document.getElementById('passingMarks').value) || 40;
        
        if (marks > maxMarks) {
            this.value = maxMarks;
            return;
        }
        
        const result = calculateGrade(marks, maxMarks);
        const gradeEl = document.getElementById('grade-' + candidateId);
        const statusEl = document.getElementById('status-' + candidateId);
        
        gradeEl.textContent = result.grade;
        gradeEl.className = 'grade-badge w-20 text-center font-semibold badge badge-' + result.class;
        
        statusEl.textContent = marks >= passingMarks ? 'Pass' : 'Fail';
        statusEl.className = 'status-badge w-24 text-center text-xs font-medium badge badge-' + 
                            (marks >= passingMarks ? 'success' : 'danger');
    });
});

// Update display when max/passing marks change
document.getElementById('maxMarks').addEventListener('input', function() {
    document.getElementById('displayMaxMarks').textContent = this.value;
});
document.getElementById('passingMarks').addEventListener('input', function() {
    document.getElementById('displayPassingMarks').textContent = this.value;
});
</script>
@endpush
@endsection
