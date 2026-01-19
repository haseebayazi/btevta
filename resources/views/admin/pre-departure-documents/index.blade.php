@extends('layouts.admin')

@section('title', 'Pre-Departure Documents - ' . $candidate->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Candidate Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pre-Departure Documents</h1>
                <p class="text-gray-600 mt-1">
                    Candidate: <span class="font-semibold">{{ $candidate->name }}</span>
                    ({{ $candidate->btevta_id }})
                </p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $candidate->status_badge_class }}">
                    {{ $candidate->status_label }}
                </span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>Document Completion</span>
                <span>{{ $completionPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                     style="width: {{ $completionPercentage }}%"></div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Document Checklist -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Document Checklist</h2>

        <div class="space-y-4">
            @foreach($documentChecklist as $doc)
                @php
                    $uploaded = $candidateDocuments->firstWhere('document_checklist_id', $doc->id);
                    $isUploaded = $uploaded !== null;
                    $isVerified = $uploaded && $uploaded->is_verified;
                @endphp

                <div class="border rounded-lg p-4 {{ $isVerified ? 'border-green-300 bg-green-50' : ($isUploaded ? 'border-yellow-300 bg-yellow-50' : 'border-gray-300') }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-800">{{ $doc->name }}</h3>
                                @if($doc->is_mandatory)
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded">
                                        Mandatory
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                                        Optional
                                    </span>
                                @endif
                            </div>

                            @if($doc->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $doc->description }}</p>
                            @endif

                            @if($isUploaded)
                                <div class="mt-2 flex items-center gap-4 text-sm">
                                    <span class="text-gray-600">
                                        Uploaded: {{ $uploaded->created_at->format('d M Y, h:i A') }}
                                    </span>
                                    @if($isVerified)
                                        <span class="flex items-center gap-1 text-green-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Verified
                                        </span>
                                    @else
                                        <span class="text-yellow-600">Pending Verification</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            @if($isUploaded)
                                <!-- Download Button -->
                                <a href="{{ route('admin.pre-departure-documents.download', ['candidate' => $candidate->id, 'document' => $uploaded->id]) }}"
                                   class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                    Download
                                </a>

                                @can('update', $uploaded)
                                    @if(!$isVerified)
                                        <!-- Verify Button -->
                                        <form action="{{ route('admin.pre-departure-documents.verify', ['candidate' => $candidate->id, 'document' => $uploaded->id]) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                                Verify
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            @else
                                <!-- Upload Button -->
                                <button onclick="openUploadModal({{ $doc->id }}, '{{ $doc->name }}')"
                                        class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                    Upload
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Bulk Upload Button -->
        @can('create', App\Models\PreDepartureDocument::class)
            <div class="mt-6 pt-6 border-t">
                <button onclick="openBulkUploadModal()"
                        class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                    Bulk Upload Documents
                </button>
            </div>
        @endcan
    </div>

    <!-- Back Button -->
    <div class="flex justify-end">
        <a href="{{ route('admin.candidates.show', $candidate) }}"
           class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Candidate
        </a>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Upload Document</h3>

            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="document_checklist_id" id="documentChecklistId">

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Select File
                    </label>
                    <input type="file"
                           name="document"
                           accept=".pdf,.jpg,.jpeg,.png"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Allowed: PDF, JPG, PNG (Max 10MB)</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Notes (Optional)
                    </label>
                    <textarea name="notes"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button"
                            onclick="closeUploadModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Upload Modal -->
<div id="bulkUploadModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Bulk Upload Documents</h3>

            <form action="{{ route('admin.pre-departure-documents.bulk-upload', $candidate) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Select Multiple Files
                    </label>
                    <input type="file"
                           name="documents[]"
                           accept=".pdf,.jpg,.jpeg,.png"
                           multiple
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Upload multiple documents at once</p>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button"
                            onclick="closeBulkUploadModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                        Upload All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openUploadModal(checklistId, documentName) {
    document.getElementById('documentChecklistId').value = checklistId;
    document.getElementById('modalTitle').textContent = 'Upload ' + documentName;
    document.getElementById('uploadForm').action = "{{ route('admin.pre-departure-documents.store', $candidate) }}";
    document.getElementById('uploadModal').classList.remove('hidden');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

function openBulkUploadModal() {
    document.getElementById('bulkUploadModal').classList.remove('hidden');
}

function closeBulkUploadModal() {
    document.getElementById('bulkUploadModal').classList.add('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const uploadModal = document.getElementById('uploadModal');
    const bulkModal = document.getElementById('bulkUploadModal');
    if (event.target === uploadModal) {
        closeUploadModal();
    }
    if (event.target === bulkModal) {
        closeBulkUploadModal();
    }
}
</script>
@endpush
@endsection
