@extends('layouts.app')

@section('title', 'Report Post-Departure Issue')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Report Post-Departure Issue</h1>
        <p class="text-gray-600 mt-1">Document and report issues faced by departed candidates</p>
    </div>

    <div class="card">
        <form action="{{ route('departure.issues.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Candidate Selection -->
                <div class="md:col-span-2">
                    <label for="candidate_id" class="form-label">Select Candidate <span class="text-red-500">*</span></label>
                    <select id="candidate_id" name="candidate_id"
                            class="form-select w-full @error('candidate_id') border-red-500 @enderror" required>
                        <option value="">-- Select Departed Candidate --</option>
                        @foreach($candidates as $candidate)
                            <option value="{{ $candidate->id }}" {{ old('candidate_id') == $candidate->id ? 'selected' : '' }}>
                                {{ $candidate->name }} ({{ $candidate->btevta_id }})
                                @if($candidate->departure)
                                    - Departed: {{ $candidate->departure->departure_date?->format('M d, Y') ?? 'N/A' }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('candidate_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Issue Type -->
                <div>
                    <label for="issue_type" class="form-label">Issue Type <span class="text-red-500">*</span></label>
                    <select id="issue_type" name="issue_type"
                            class="form-select w-full @error('issue_type') border-red-500 @enderror" required>
                        <option value="">Select Issue Type</option>
                        <option value="salary_delay" {{ old('issue_type') == 'salary_delay' ? 'selected' : '' }}>
                            Salary Delay
                        </option>
                        <option value="contract_violation" {{ old('issue_type') == 'contract_violation' ? 'selected' : '' }}>
                            Contract Violation
                        </option>
                        <option value="work_condition" {{ old('issue_type') == 'work_condition' ? 'selected' : '' }}>
                            Work Condition Issues
                        </option>
                        <option value="accommodation" {{ old('issue_type') == 'accommodation' ? 'selected' : '' }}>
                            Accommodation Problems
                        </option>
                        <option value="medical" {{ old('issue_type') == 'medical' ? 'selected' : '' }}>
                            Medical Issues
                        </option>
                        <option value="other" {{ old('issue_type') == 'other' ? 'selected' : '' }}>
                            Other
                        </option>
                    </select>
                    @error('issue_type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Severity -->
                <div>
                    <label for="severity" class="form-label">Severity <span class="text-red-500">*</span></label>
                    <select id="severity" name="severity"
                            class="form-select w-full @error('severity') border-red-500 @enderror" required>
                        <option value="">Select Severity</option>
                        <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>
                            Low - Minor inconvenience
                        </option>
                        <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>
                            Medium - Requires attention
                        </option>
                        <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>
                            High - Urgent matter
                        </option>
                        <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>
                            Critical - Immediate action needed
                        </option>
                    </select>
                    @error('severity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Issue Date -->
                <div>
                    <label for="issue_date" class="form-label">Issue Date <span class="text-red-500">*</span></label>
                    <input type="date" id="issue_date" name="issue_date"
                           value="{{ old('issue_date', date('Y-m-d')) }}"
                           class="form-input w-full @error('issue_date') border-red-500 @enderror" required>
                    @error('issue_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Evidence File -->
                <div>
                    <label for="evidence" class="form-label">Evidence (Optional)</label>
                    <input type="file" id="evidence" name="evidence"
                           class="form-input w-full @error('evidence') border-red-500 @enderror"
                           accept=".pdf,.jpg,.jpeg,.png">
                    <p class="text-sm text-gray-500 mt-1">Accepted: PDF, JPG, PNG (Max 10MB)</p>
                    @error('evidence')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Issue Description -->
                <div class="md:col-span-2">
                    <label for="issue_description" class="form-label">Issue Description <span class="text-red-500">*</span></label>
                    <textarea id="issue_description" name="issue_description" rows="5"
                              class="form-input w-full @error('issue_description') border-red-500 @enderror"
                              placeholder="Provide a detailed description of the issue..." required>{{ old('issue_description') }}</textarea>
                    @error('issue_description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Severity Guide -->
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-700 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Severity Guide
                </h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-start">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500 mt-1 mr-2"></span>
                        <div>
                            <strong>Low:</strong> Minor delays, small inconveniences that don't affect overall wellbeing
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="inline-block w-3 h-3 rounded-full bg-yellow-500 mt-1 mr-2"></span>
                        <div>
                            <strong>Medium:</strong> Issues requiring attention within a week, moderate impact
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="inline-block w-3 h-3 rounded-full bg-orange-500 mt-1 mr-2"></span>
                        <div>
                            <strong>High:</strong> Urgent issues needing resolution within 48 hours
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="inline-block w-3 h-3 rounded-full bg-red-500 mt-1 mr-2"></span>
                        <div>
                            <strong>Critical:</strong> Safety concerns, contract breaches, or health emergencies
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Report Issue
                </button>
                <a href="{{ route('departure.active-issues') }}" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
