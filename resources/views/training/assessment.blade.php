@extends('layouts.app')

@section('title', 'Record Assessment - ' . $candidate->name)

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('training.index') }}">Training</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('training.show', $candidate) }}">{{ $candidate->btevta_id }}</a></li>
                    <li class="breadcrumb-item active">Assessment</li>
                </ol>
            </nav>
            <h2 class="mb-0">Record Assessment</h2>
            <p class="text-muted mb-0">{{ $candidate->name }} - {{ $candidate->trade->name ?? 'N/A' }}</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('training.show', $candidate) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="row">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>New Assessment</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('training.store-assessment', $candidate) }}" method="POST" id="assessmentForm">
                        @csrf

                        {{-- Assessment Type Tabs --}}
                        <div class="mb-4">
                            <label class="font-weight-bold d-block mb-2">Assessment Type <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-primary {{ old('assessment_type', 'theory') === 'theory' ? 'active' : '' }}">
                                    <input type="radio" name="assessment_type" value="theory" {{ old('assessment_type', 'theory') === 'theory' ? 'checked' : '' }}>
                                    <i class="fas fa-book mr-1"></i> Theory
                                </label>
                                <label class="btn btn-outline-primary {{ old('assessment_type') === 'practical' ? 'active' : '' }}">
                                    <input type="radio" name="assessment_type" value="practical" {{ old('assessment_type') === 'practical' ? 'checked' : '' }}>
                                    <i class="fas fa-tools mr-1"></i> Practical
                                </label>
                                <label class="btn btn-outline-primary {{ old('assessment_type') === 'final' ? 'active' : '' }}">
                                    <input type="radio" name="assessment_type" value="final" {{ old('assessment_type') === 'final' ? 'checked' : '' }}>
                                    <i class="fas fa-graduation-cap mr-1"></i> Final Exam
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Assessment Date --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Assessment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="assessment_date" class="form-control @error('assessment_date') is-invalid @enderror"
                                           value="{{ old('assessment_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                                    @error('assessment_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Total Marks --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Total Marks <span class="text-danger">*</span></label>
                                    <input type="number" name="total_marks" id="totalMarks" class="form-control @error('total_marks') is-invalid @enderror"
                                           value="{{ old('total_marks', 100) }}" min="1" required>
                                    @error('total_marks')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Obtained Marks --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="font-weight-bold">Obtained Marks <span class="text-danger">*</span></label>
                                    <input type="number" name="obtained_marks" id="obtainedMarks" class="form-control @error('obtained_marks') is-invalid @enderror"
                                           value="{{ old('obtained_marks') }}" min="0" required>
                                    @error('obtained_marks')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Grade (Auto-calculated) --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Grade <span class="text-danger">*</span></label>
                                    <select name="grade" id="gradeSelect" class="form-control @error('grade') is-invalid @enderror" required>
                                        <option value="">Select Grade</option>
                                        <option value="A+" {{ old('grade') === 'A+' ? 'selected' : '' }}>A+ (90-100%)</option>
                                        <option value="A" {{ old('grade') === 'A' ? 'selected' : '' }}>A (80-89%)</option>
                                        <option value="B" {{ old('grade') === 'B' ? 'selected' : '' }}>B (70-79%)</option>
                                        <option value="C" {{ old('grade') === 'C' ? 'selected' : '' }}>C (60-69%)</option>
                                        <option value="D" {{ old('grade') === 'D' ? 'selected' : '' }}>D (50-59%)</option>
                                        <option value="F" {{ old('grade') === 'F' ? 'selected' : '' }}>F (Below 50%)</option>
                                    </select>
                                    @error('grade')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Result Display --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Result</label>
                                    <div class="form-control-plaintext">
                                        <span id="resultBadge" class="badge badge-secondary px-3 py-2">--</span>
                                        <span id="percentageDisplay" class="ml-2 text-muted">--%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div class="form-group">
                            <label class="font-weight-bold">Remarks</label>
                            <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="3"
                                      placeholder="Add remarks about the assessment...">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-between pt-4 border-top">
                            <a href="{{ route('training.show', $candidate) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Assessment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Candidate Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Candidate Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>BTEVTA ID:</strong> <span class="text-monospace">{{ $candidate->btevta_id }}</span></p>
                    <p class="mb-2"><strong>Name:</strong> {{ $candidate->name }}</p>
                    <p class="mb-2"><strong>Trade:</strong> {{ $candidate->trade->name ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Batch:</strong> {{ $candidate->batch->name ?? 'N/A' }}</p>
                </div>
            </div>

            {{-- Grading Guide --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Grading Guide</h5>
                </div>
                <div class="card-body small">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td>A+ (Excellent)</td><td class="text-right">90% - 100%</td></tr>
                            <tr><td>A (Very Good)</td><td class="text-right">80% - 89%</td></tr>
                            <tr><td>B (Good)</td><td class="text-right">70% - 79%</td></tr>
                            <tr><td>C (Average)</td><td class="text-right">60% - 69%</td></tr>
                            <tr><td>D (Pass)</td><td class="text-right">50% - 59%</td></tr>
                            <tr class="text-danger"><td>F (Fail)</td><td class="text-right">Below 50%</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Previous Assessments --}}
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Previous Assessments</h5>
                </div>
                <div class="card-body">
                    @if($candidate->assessments && $candidate->assessments->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($candidate->assessments->take(5) as $assessment)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ ucfirst($assessment->assessment_type) }}</strong>
                                            <small class="text-muted d-block">{{ $assessment->assessment_date ? $assessment->assessment_date->format('d M Y') : 'N/A' }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-weight-bold">{{ $assessment->total_score }}/{{ $assessment->max_score }}</span>
                                            <span class="badge badge-{{ $assessment->result === 'pass' ? 'success' : 'danger' }} d-block mt-1">
                                                {{ ucfirst($assessment->result) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No previous assessments recorded.</p>
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
        let resultClass = 'danger';
        let resultText = 'Fail';

        if (percentage >= 90) {
            grade = 'A+'; resultClass = 'success'; resultText = 'Excellent';
        } else if (percentage >= 80) {
            grade = 'A'; resultClass = 'success'; resultText = 'Very Good';
        } else if (percentage >= 70) {
            grade = 'B'; resultClass = 'info'; resultText = 'Good';
        } else if (percentage >= 60) {
            grade = 'C'; resultClass = 'warning'; resultText = 'Average';
        } else if (percentage >= 50) {
            grade = 'D'; resultClass = 'warning'; resultText = 'Pass';
        } else {
            grade = 'F'; resultClass = 'danger'; resultText = 'Fail';
        }

        // Update grade select
        document.getElementById('gradeSelect').value = grade;

        // Update result display
        document.getElementById('resultBadge').textContent = resultText;
        document.getElementById('resultBadge').className = 'badge badge-' + resultClass + ' px-3 py-2';
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
