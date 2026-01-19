@extends('layouts.admin')

@section('title', isset($complaint) ? 'Edit Complaint' : 'Register Complaint')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ isset($complaint) ? 'Edit Complaint' : 'Register New Complaint' }}
                </h1>
                <p class="text-gray-600 mt-1">Enhanced WASL v3 complaint workflow with structured resolution tracking</p>
            </div>
            <a href="{{ route('admin.complaints.index') }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to Complaints
            </a>
        </div>
    </div>

    <!-- Candidate Selection (if not pre-selected) -->
    @if(!isset($candidate) && !isset($complaint))
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-6">
        <p class="text-yellow-800 text-sm">
            <strong>Note:</strong> Please select a candidate before registering a complaint.
            <a href="{{ route('admin.candidates.index') }}" class="underline font-semibold">Go to Candidates List</a>
        </p>
    </div>
    @endif

    <!-- Candidate Information Panel -->
    @if(isset($candidate))
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Candidate:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->full_name }}</strong>
            </div>
            <div>
                <span class="text-gray-600">BTEVTA ID:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->btevta_id }}</strong>
            </div>
            <div>
                <span class="text-gray-600">Status:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->status }}</strong>
            </div>
            <div>
                <span class="text-gray-600">Current Stage:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->current_module ?? 'N/A' }}</strong>
            </div>
        </div>
    </div>
    @endif

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

    <form action="{{ isset($complaint) ? route('admin.complaints.update', $complaint) : route('admin.complaints.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($complaint))
            @method('PUT')
        @else
            <input type="hidden" name="candidate_id" value="{{ $candidate->id ?? '' }}">
        @endif

        <!-- Basic Complaint Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Basic Complaint Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Complaint Category -->
                <div>
                    <label for="complaint_category" class="block text-gray-700 font-medium mb-2">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select name="complaint_category"
                            id="complaint_category"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Category --</option>
                        @foreach(\App\Models\Complaint::getCategories() as $key => $label)
                            <option value="{{ $key }}"
                                    {{ old('complaint_category', $complaint->complaint_category ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority -->
                <div>
                    <label for="priority" class="block text-gray-700 font-medium mb-2">
                        Priority <span class="text-red-500">*</span>
                    </label>
                    <select name="priority"
                            id="priority"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\Complaint::getPriorities() as $key => $label)
                            <option value="{{ $key }}"
                                    {{ old('priority', $complaint->priority ?? 'normal') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Subject/Title -->
                <div class="md:col-span-2">
                    <label for="subject" class="block text-gray-700 font-medium mb-2">
                        Subject/Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="subject"
                           id="subject"
                           value="{{ old('subject', $complaint->subject ?? '') }}"
                           required
                           placeholder="Brief summary of the complaint"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-gray-700 font-medium mb-2">
                        Initial Description <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="4"
                              required
                              placeholder="Provide a detailed description of the complaint when first reported"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $complaint->description ?? '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Initial complaint as reported by candidate or identified by staff</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Workflow Section (WASL v3) -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
                Enhanced Workflow (WASL v3)
            </h2>

            <!-- Current Issue -->
            <div class="mb-6">
                <label for="current_issue" class="block text-gray-700 font-medium mb-2">
                    1. Current Issue Analysis
                </label>
                <textarea name="current_issue"
                          id="current_issue"
                          rows="4"
                          placeholder="Analyze and document the current state of the issue:
- What is the specific problem?
- Who is affected and how?
- What is the current impact on the candidate?
- Any immediate concerns or risks?"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('current_issue', $complaint->current_issue ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Detailed analysis of the issue after investigation</p>
            </div>

            <!-- Support Steps Taken -->
            <div class="mb-6">
                <label for="support_steps_taken" class="block text-gray-700 font-medium mb-2">
                    2. Support Steps Taken
                </label>
                <textarea name="support_steps_taken"
                          id="support_steps_taken"
                          rows="5"
                          placeholder="Document all actions taken to resolve the complaint:
- Date and time of each action
- Who was contacted (names, positions)
- What was communicated
- Resources provided to candidate
- Follow-up actions scheduled
- Escalations made"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('support_steps_taken', $complaint->support_steps_taken ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Chronological record of all support activities and interventions</p>
            </div>

            <!-- Suggestions -->
            <div class="mb-6">
                <label for="suggestions" class="block text-gray-700 font-medium mb-2">
                    3. Suggestions & Recommendations
                </label>
                <textarea name="suggestions"
                          id="suggestions"
                          rows="4"
                          placeholder="Provide suggestions for resolution:
- Recommended next steps
- Alternative solutions considered
- Resources needed
- Preventive measures for future
- Policy changes suggested"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('suggestions', $complaint->suggestions ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Recommendations for resolving the issue and preventing recurrence</p>
            </div>

            <!-- Conclusion -->
            <div class="mb-6">
                <label for="conclusion" class="block text-gray-700 font-medium mb-2">
                    4. Conclusion & Resolution
                </label>
                <textarea name="conclusion"
                          id="conclusion"
                          rows="4"
                          placeholder="Final resolution and outcome:
- How was the issue resolved?
- Candidate satisfaction level
- Lessons learned
- Follow-up requirements
- Case closure notes"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('conclusion', $complaint->conclusion ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Final outcome and closure details (completed when complaint is resolved)</p>
            </div>

            <!-- Complaint Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-gray-700 font-medium mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status"
                            id="status"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\Complaint::getStatuses() as $key => $label)
                            <option value="{{ $key }}"
                                    {{ old('status', $complaint->status ?? 'open') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="assigned_to" class="block text-gray-700 font-medium mb-2">
                        Assign To
                    </label>
                    <select name="assigned_to"
                            id="assigned_to"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Not Assigned --</option>
                        @foreach($users ?? [] as $user)
                            <option value="{{ $user->id }}"
                                    {{ old('assigned_to', $complaint->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->role }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Evidence Upload Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Supporting Evidence</h2>

            @if(isset($complaint) && $complaint->evidence_path)
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800 mb-2">
                        <strong>Current Evidence ({{ ucfirst($complaint->evidence_type ?? 'document') }}):</strong>
                        {{ basename($complaint->evidence_path) }}
                    </p>
                    <a href="{{ route('admin.complaints.download-evidence', $complaint) }}"
                       class="text-blue-600 hover:text-blue-800 text-sm underline">
                        Download Current Evidence
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Evidence Type -->
                <div>
                    <label for="evidence_type" class="block text-gray-700 font-medium mb-2">
                        Evidence Type
                    </label>
                    <select name="evidence_type"
                            id="evidence_type"
                            onchange="updateEvidenceAccept(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Type --</option>
                        <option value="audio" {{ old('evidence_type', $complaint->evidence_type ?? '') === 'audio' ? 'selected' : '' }}>Audio Recording</option>
                        <option value="video" {{ old('evidence_type', $complaint->evidence_type ?? '') === 'video' ? 'selected' : '' }}>Video Recording</option>
                        <option value="screenshot" {{ old('evidence_type', $complaint->evidence_type ?? '') === 'screenshot' ? 'selected' : '' }}>Screenshot/Image</option>
                        <option value="document" {{ old('evidence_type', $complaint->evidence_type ?? '') === 'document' ? 'selected' : '' }}>Document</option>
                        <option value="other" {{ old('evidence_type', $complaint->evidence_type ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <!-- Evidence File Upload -->
                <div>
                    <label for="evidence_file" class="block text-gray-700 font-medium mb-2">
                        Upload Evidence File
                    </label>
                    <input type="file"
                           name="evidence_file"
                           id="evidence_file"
                           accept="*/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="evidence_help" class="text-xs text-gray-500 mt-1">
                        Upload audio, video, screenshots, or documents as evidence
                    </p>
                </div>
            </div>

            <!-- Evidence Guidelines -->
            <div class="mt-4 bg-gray-50 border border-gray-200 rounded-md p-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Evidence Types:</h3>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    <li><strong>Audio:</strong> Phone call recordings, voice messages (Max 50MB)</li>
                    <li><strong>Video:</strong> Video evidence of issue, site conditions (Max 200MB)</li>
                    <li><strong>Screenshot:</strong> Chat conversations, emails, documents (Max 10MB)</li>
                    <li><strong>Document:</strong> Contracts, letters, official documents (Max 10MB)</li>
                    <li><strong>Other:</strong> Any other supporting material</li>
                </ul>
            </div>
        </div>

        <!-- Resolution Details (if updating) -->
        @if(isset($complaint))
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Resolution Details</h2>

            <div>
                <label for="resolution_details" class="block text-gray-700 font-medium mb-2">
                    Resolution Notes
                </label>
                <textarea name="resolution_details"
                          id="resolution_details"
                          rows="4"
                          placeholder="Enter resolution details when closing the complaint"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('resolution_details', $complaint->resolution_details ?? '') }}</textarea>
            </div>
        </div>
        @endif

        <!-- Workflow Guidelines -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Enhanced Workflow Guidelines</h2>

            <div class="bg-yellow-50 border border-yellow-300 rounded-md p-4">
                <h3 class="text-sm font-semibold text-yellow-800 mb-2">Complaint Resolution Workflow:</h3>
                <ol class="list-decimal list-inside text-sm text-yellow-700 space-y-2">
                    <li><strong>Candidate Selection:</strong> Link complaint to specific candidate</li>
                    <li><strong>Issue Documentation:</strong> Record detailed description in "Current Issue Analysis"</li>
                    <li><strong>Support Actions:</strong> Document all steps taken in "Support Steps Taken" section</li>
                    <li><strong>Recommendations:</strong> Provide suggestions for resolution</li>
                    <li><strong>Conclusion:</strong> Document final resolution and outcomes</li>
                    <li><strong>Evidence:</strong> Attach supporting evidence (audio, video, screenshots, documents)</li>
                    <li><strong>SLA Tracking:</strong> Monitor and meet category-specific resolution timelines</li>
                    <li><strong>Follow-up:</strong> Ensure candidate satisfaction and case closure</li>
                </ol>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('admin.complaints.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                {{ isset($complaint) ? 'Update Complaint' : 'Register Complaint' }}
            </button>
        </div>
    </form>
</div>

<!-- JavaScript for dynamic evidence file acceptance -->
<script>
    function updateEvidenceAccept(type) {
        const fileInput = document.getElementById('evidence_file');
        const helpText = document.getElementById('evidence_help');

        switch(type) {
            case 'audio':
                fileInput.accept = 'audio/*';
                helpText.textContent = 'Upload audio recording (MP3, M4A, WAV - Max 50MB)';
                break;
            case 'video':
                fileInput.accept = 'video/*';
                helpText.textContent = 'Upload video file (MP4, MOV, AVI - Max 200MB)';
                break;
            case 'screenshot':
                fileInput.accept = 'image/*';
                helpText.textContent = 'Upload screenshot or image (JPG, PNG - Max 10MB)';
                break;
            case 'document':
                fileInput.accept = '.pdf,.doc,.docx';
                helpText.textContent = 'Upload document (PDF, DOC, DOCX - Max 10MB)';
                break;
            case 'other':
                fileInput.accept = '*/*';
                helpText.textContent = 'Upload any supporting file (Max 50MB)';
                break;
            default:
                fileInput.accept = '*/*';
                helpText.textContent = 'Upload audio, video, screenshots, or documents as evidence';
        }
    }

    // Initialize on page load if evidence type is already selected
    document.addEventListener('DOMContentLoaded', function() {
        const evidenceType = document.getElementById('evidence_type').value;
        if (evidenceType) {
            updateEvidenceAccept(evidenceType);
        }
    });
</script>
@endsection
