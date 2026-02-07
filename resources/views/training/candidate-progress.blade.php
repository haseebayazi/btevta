@extends('layouts.app')

@section('title', 'Training Progress - ' . $training->candidate->name)

@section('content')
<div class="container-fluid py-4">
    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.index') }}" class="hover:text-blue-600">Training</a>
                <span class="mx-1">/</span>
                @if($training->batch)
                <a href="{{ route('training.dual-status-dashboard', $training->batch) }}" class="hover:text-blue-600">{{ $training->batch->name ?? 'Batch' }}</a>
                <span class="mx-1">/</span>
                @endif
                <span class="text-gray-700">Progress</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-800">Training Progress</h2>
            <p class="text-gray-500 text-sm">{{ $training->candidate->name }} - {{ $training->candidate->btevta_id }}</p>
        </div>
        <div class="mt-3 sm:mt-0 flex space-x-2">
            <a href="{{ route('training.show', $training->candidate) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-user mr-1"></i> Full Profile
            </a>
            @if($training->batch)
            <a href="{{ route('training.dual-status-dashboard', $training->batch) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Batch
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Overall Progress --}}
    <div class="bg-white rounded-xl shadow-sm border p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-gray-800">Overall Completion</h4>
            <span class="text-2xl font-bold {{ $progress['overall']['completion_percentage'] === 100 ? 'text-green-600' : 'text-blue-600' }}">
                {{ $progress['overall']['completion_percentage'] }}%
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full transition-all duration-500 {{ $progress['overall']['completion_percentage'] === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                 style="width: {{ $progress['overall']['completion_percentage'] }}%"></div>
        </div>
        @if($progress['overall']['both_complete'])
        <div class="mt-3 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-check-double mr-2"></i>Both training tracks completed. Certificate can be generated.
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Technical Training Track --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="bg-blue-50 border-b border-blue-100 p-4 flex items-center justify-between">
                <h4 class="font-semibold text-blue-800">
                    <i class="fas fa-tools mr-2"></i>Technical Training
                </h4>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    {{ $progress['technical']['status']->value === 'completed' ? 'bg-green-100 text-green-800' : ($progress['technical']['status']->value === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                    <i class="{{ $progress['technical']['status']->icon() }} mr-1"></i>{{ $progress['technical']['status_label'] }}
                </span>
            </div>
            <div class="p-4">
                @if($progress['technical']['completed_at'])
                <p class="text-sm text-gray-500 mb-3">Completed: {{ $progress['technical']['completed_at'] }}</p>
                @endif

                <h5 class="font-medium text-gray-700 mb-2 text-sm">Assessments</h5>
                @if($progress['technical']['assessments']->count() > 0)
                <div class="space-y-2 mb-4">
                    @foreach($progress['technical']['assessments'] as $a)
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-sm">
                        <div class="flex items-center">
                            <i class="fas {{ $a['passed'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} mr-2"></i>
                            <span class="font-medium capitalize">{{ $a['type'] }}</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-600">{{ $a['score'] }}/{{ $a['max_score'] }} ({{ $a['percentage'] }}%)</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $a['grade'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 mb-4">No technical assessments recorded yet.</p>
                @endif

                @if($progress['technical']['can_complete'] && $progress['technical']['status']->value !== 'completed')
                <form action="{{ route('training.complete-training-type', $training) }}" method="POST">
                    @csrf
                    <input type="hidden" name="training_type" value="technical">
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm"
                            onclick="return confirm('Mark technical training as complete?')">
                        <i class="fas fa-check mr-1"></i> Complete Technical Training
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Soft Skills Training Track --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="bg-purple-50 border-b border-purple-100 p-4 flex items-center justify-between">
                <h4 class="font-semibold text-purple-800">
                    <i class="fas fa-comments mr-2"></i>Soft Skills Training
                </h4>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    {{ $progress['soft_skills']['status']->value === 'completed' ? 'bg-green-100 text-green-800' : ($progress['soft_skills']['status']->value === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                    <i class="{{ $progress['soft_skills']['status']->icon() }} mr-1"></i>{{ $progress['soft_skills']['status_label'] }}
                </span>
            </div>
            <div class="p-4">
                @if($progress['soft_skills']['completed_at'])
                <p class="text-sm text-gray-500 mb-3">Completed: {{ $progress['soft_skills']['completed_at'] }}</p>
                @endif

                <h5 class="font-medium text-gray-700 mb-2 text-sm">Assessments</h5>
                @if($progress['soft_skills']['assessments']->count() > 0)
                <div class="space-y-2 mb-4">
                    @foreach($progress['soft_skills']['assessments'] as $a)
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-sm">
                        <div class="flex items-center">
                            <i class="fas {{ $a['passed'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} mr-2"></i>
                            <span class="font-medium capitalize">{{ $a['type'] }}</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-600">{{ $a['score'] }}/{{ $a['max_score'] }} ({{ $a['percentage'] }}%)</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">{{ $a['grade'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 mb-4">No soft skills assessments recorded yet.</p>
                @endif

                @if($progress['soft_skills']['can_complete'] && $progress['soft_skills']['status']->value !== 'completed')
                <form action="{{ route('training.complete-training-type', $training) }}" method="POST">
                    @csrf
                    <input type="hidden" name="training_type" value="soft_skills">
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm"
                            onclick="return confirm('Mark soft skills training as complete?')">
                        <i class="fas fa-check mr-1"></i> Complete Soft Skills Training
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Attendance Summary --}}
    <div class="bg-white rounded-xl shadow-sm border p-5 mb-6">
        <h4 class="font-semibold text-gray-800 mb-3"><i class="fas fa-clipboard-list mr-2"></i>Attendance Summary</h4>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-gray-800">{{ $attendanceStats['total_sessions'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Total Sessions</p>
            </div>
            <div class="text-center p-3 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $attendanceStats['present'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Present</p>
            </div>
            <div class="text-center p-3 bg-red-50 rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ $attendanceStats['absent'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Absent</p>
            </div>
            <div class="text-center p-3 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">{{ $attendanceStats['late'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Late</p>
            </div>
            <div class="text-center p-3 {{ ($attendanceStats['percentage'] ?? 0) >= 80 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg">
                <p class="text-2xl font-bold {{ ($attendanceStats['percentage'] ?? 0) >= 80 ? 'text-green-600' : 'text-red-600' }}">{{ $attendanceStats['percentage'] ?? 0 }}%</p>
                <p class="text-xs text-gray-500">Rate</p>
            </div>
        </div>
    </div>

    {{-- Record New Assessment Form --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden" x-data="{ showForm: false }">
        <div class="p-5 border-b cursor-pointer flex items-center justify-between" @click="showForm = !showForm">
            <h4 class="font-semibold text-gray-800"><i class="fas fa-plus-circle mr-2 text-blue-500"></i>Record New Assessment</h4>
            <i class="fas" :class="showForm ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
        </div>
        <div x-show="showForm" x-collapse class="p-5">
            <form action="{{ route('training.store-typed-assessment', $training) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="candidate_id" value="{{ $training->candidate_id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Training Type</label>
                        <select name="training_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Type...</option>
                            <option value="technical">Technical Training</option>
                            <option value="soft_skills">Soft Skills Training</option>
                        </select>
                        @error('training_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assessment Type</label>
                        <select name="assessment_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Assessment...</option>
                            <option value="initial">Initial Assessment</option>
                            <option value="interim">Interim Assessment</option>
                            <option value="midterm">Midterm Assessment</option>
                            <option value="practical">Practical Assessment</option>
                            <option value="final">Final Assessment</option>
                        </select>
                        @error('assessment_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Score</label>
                        <input type="number" name="score" min="0" max="100" step="0.01" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., 75">
                        @error('score')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Score</label>
                        <input type="number" name="max_score" min="1" max="100" value="100" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('max_score')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Optional assessment remarks...">{{ old('notes') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Evidence (Optional)</label>
                    <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-gray-400 mt-1">Accepted: PDF, JPG, PNG (max 10MB)</p>
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm">
                    <i class="fas fa-save mr-1"></i> Record Assessment
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
