@extends('layouts.admin')

@section('title', isset($successStory) ? 'Edit Success Story' : 'Record Success Story')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ isset($successStory) ? 'Edit Success Story' : 'Record Success Story' }}
                </h1>
                <p class="text-gray-600 mt-1">Document candidate success with written notes and multimedia evidence</p>
            </div>
            <a href="{{ route('admin.success-stories.index') }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to List
            </a>
        </div>
    </div>

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
                <span class="text-gray-600">Destination:</span>
                <strong class="text-gray-800 ml-2">{{ $candidate->departure->destination ?? 'N/A' }}</strong>
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

    <form action="{{ isset($successStory) ? route('admin.success-stories.update', $successStory) : route('admin.success-stories.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($successStory))
            @method('PUT')
        @else
            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
            @if(isset($departure))
                <input type="hidden" name="departure_id" value="{{ $departure->id }}">
            @endif
        @endif

        <!-- Written Success Story Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Written Success Story</h2>

            <div>
                <label for="written_note" class="block text-gray-700 font-medium mb-2">
                    Success Story Description <span class="text-red-500">*</span>
                </label>
                <textarea name="written_note"
                          id="written_note"
                          rows="8"
                          required
                          placeholder="Write the candidate's success story including:
- Journey overview (from screening to current status)
- Challenges faced and overcome
- Current employment situation and achievements
- Benefits/salary improvements
- Family impact and life changes
- Words of advice for future candidates"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('written_note', $successStory->written_note ?? '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Provide a detailed narrative of the candidate's success story</p>
            </div>

            <!-- Recording Date and By -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label for="recorded_at" class="block text-gray-700 font-medium mb-2">
                        Recording Date & Time <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local"
                           name="recorded_at"
                           id="recorded_at"
                           value="{{ old('recorded_at', isset($successStory) && $successStory->recorded_at ? $successStory->recorded_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="is_featured" class="block text-gray-700 font-medium mb-2">
                        Feature this Story?
                    </label>
                    <select name="is_featured"
                            id="is_featured"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('is_featured', $successStory->is_featured ?? 0) == 0 ? 'selected' : '' }}>No - Regular Story</option>
                        <option value="1" {{ old('is_featured', $successStory->is_featured ?? 0) == 1 ? 'selected' : '' }}>Yes - Featured Story</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Featured stories appear on homepage/reports</p>
                </div>
            </div>
        </div>

        <!-- Evidence Upload Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Multimedia Evidence (Optional)</h2>

            @if(isset($successStory) && $successStory->evidence_path)
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800 mb-2">
                        <strong>Current Evidence ({{ ucfirst($successStory->evidence_type) }}):</strong>
                        {{ $successStory->evidence_filename ?? basename($successStory->evidence_path) }}
                    </p>
                    <a href="{{ route('admin.success-stories.download-evidence', $successStory) }}"
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
                            onchange="updateEvidenceHelp(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Type --</option>
                        @foreach(\App\Enums\EvidenceType::cases() as $type)
                            <option value="{{ $type->value }}"
                                    {{ old('evidence_type', $successStory->evidence_type ?? '') === $type->value ? 'selected' : '' }}>
                                {{ ucfirst($type->value) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Type of multimedia evidence being uploaded</p>
                </div>

                <!-- Evidence File Upload -->
                <div>
                    <label for="evidence_file" class="block text-gray-700 font-medium mb-2">
                        Upload Evidence File
                    </label>
                    <input type="file"
                           name="evidence_file"
                           id="evidence_file"
                           accept="audio/*,video/*,image/*,.pdf,.doc,.docx"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="evidence_help" class="text-xs text-gray-500 mt-1">
                        Upload audio, video, images, or documents as evidence
                    </p>
                </div>
            </div>

            <!-- Evidence Guidelines -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-md p-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Evidence Guidelines:</h3>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    <li><strong>Audio:</strong> Interview recording, voice message (MP3, M4A, WAV - Max 50MB)</li>
                    <li><strong>Video:</strong> Success story video, workplace tour (MP4, MOV, AVI - Max 200MB)</li>
                    <li><strong>Written:</strong> Typed story, testimonial (PDF, DOC, DOCX - Max 10MB)</li>
                    <li><strong>Screenshot:</strong> Salary slip, work ID, achievements (JPG, PNG - Max 5MB)</li>
                    <li><strong>Document:</strong> Contract, certificates, awards (PDF - Max 10MB)</li>
                    <li><strong>Other:</strong> Any other supporting material</li>
                </ul>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Additional Information</h2>

            <div class="bg-yellow-50 border border-yellow-300 rounded-md p-4">
                <h3 class="text-sm font-semibold text-yellow-800 mb-2">Success Story Best Practices:</h3>
                <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                    <li>Focus on the candidate's journey and transformation</li>
                    <li>Include specific achievements and measurable outcomes (salary, position, etc.)</li>
                    <li>Highlight challenges overcome during the process</li>
                    <li>Mention family and community impact</li>
                    <li>Include candidate's advice for future participants</li>
                    <li>Obtain candidate consent before featuring their story publicly</li>
                    <li>Verify all information with candidate before recording</li>
                    <li>Multimedia evidence adds credibility and engagement</li>
                </ul>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('admin.success-stories.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                {{ isset($successStory) ? 'Update Success Story' : 'Save Success Story' }}
            </button>
        </div>
    </form>
</div>

<!-- JavaScript for dynamic help text -->
<script>
    function updateEvidenceHelp(type) {
        const helpText = document.getElementById('evidence_help');
        const fileInput = document.getElementById('evidence_file');

        switch(type) {
            case 'audio':
                helpText.textContent = 'Upload audio recording (MP3, M4A, WAV - Max 50MB)';
                fileInput.accept = 'audio/*';
                break;
            case 'video':
                helpText.textContent = 'Upload video file (MP4, MOV, AVI - Max 200MB)';
                fileInput.accept = 'video/*';
                break;
            case 'written':
                helpText.textContent = 'Upload written document (PDF, DOC, DOCX - Max 10MB)';
                fileInput.accept = '.pdf,.doc,.docx';
                break;
            case 'screenshot':
                helpText.textContent = 'Upload screenshot or image (JPG, PNG - Max 5MB)';
                fileInput.accept = 'image/*';
                break;
            case 'document':
                helpText.textContent = 'Upload document (PDF - Max 10MB)';
                fileInput.accept = '.pdf';
                break;
            case 'other':
                helpText.textContent = 'Upload any supporting file (Max 50MB)';
                fileInput.accept = '*/*';
                break;
            default:
                helpText.textContent = 'Upload audio, video, images, or documents as evidence';
                fileInput.accept = 'audio/*,video/*,image/*,.pdf,.doc,.docx';
        }
    }
</script>
@endsection
