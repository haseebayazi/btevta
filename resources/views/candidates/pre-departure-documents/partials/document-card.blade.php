<div class="bg-white rounded-xl border-2 {{ $document ? 'border-green-200' : 'border-amber-200' }} overflow-hidden transition hover:shadow-md">
    <!-- Card Header -->
    <div class="px-4 py-3 {{ $document ? 'bg-green-50' : 'bg-amber-50' }} border-b {{ $document ? 'border-green-100' : 'border-amber-100' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 {{ $document ? 'bg-green-500' : 'bg-amber-500' }} rounded-lg flex items-center justify-center">
                    <i class="fas fa-{{ $document ? 'check' : 'file-upload' }} text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 text-sm">{{ $checklist->name }}</h3>
                    @if($checklist->is_mandatory)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                        Required
                    </span>
                    @endif
                </div>
            </div>
            @if($document && $document->isVerified())
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <i class="fas fa-shield-alt mr-1"></i>Verified
            </span>
            @endif
        </div>
    </div>

    <!-- Card Body -->
    <div class="p-4">
        @if($document)
            <!-- Document Uploaded State -->
            <div class="space-y-3">
                <!-- File Info -->
                <div class="space-y-2">
                    <!-- Main File -->
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-white rounded-lg border border-gray-200 flex items-center justify-center flex-shrink-0">
                            @php
                                $icon = 'file';
                                if (str_contains($document->mime_type, 'pdf')) $icon = 'file-pdf';
                                elseif (str_contains($document->mime_type, 'image')) $icon = 'file-image';
                            @endphp
                            <i class="fas fa-{{ $icon }} text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $document->original_filename }}
                                @if($document->pages && $document->pages->count() > 0)
                                <span class="text-xs text-blue-600 ml-1">(Page 1)</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ number_format($document->file_size / 1024, 1) }} KB</p>
                        </div>
                    </div>

                    {{-- Additional Pages --}}
                    @if($document->pages && $document->pages->count() > 0)
                    @foreach($document->pages as $page)
                    <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <div class="w-10 h-10 bg-white rounded-lg border border-blue-200 flex items-center justify-center flex-shrink-0">
                            @php
                                $pageIcon = 'file';
                                if (str_contains($page->mime_type, 'pdf')) $pageIcon = 'file-pdf';
                                elseif (str_contains($page->mime_type, 'image')) $pageIcon = 'file-image';
                            @endphp
                            <i class="fas fa-{{ $pageIcon }} text-blue-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $page->original_filename }}
                                <span class="text-xs text-blue-600 ml-1">(Page {{ $page->page_number }})</span>
                            </p>
                            <p class="text-xs text-gray-500">{{ number_format($page->file_size / 1024, 1) }} KB</p>
                        </div>
                        @can('view', $document)
                        <a href="{{ route('candidates.pre-departure-documents.download-page', [$candidate, $document, $page]) }}"
                           class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition"
                           title="Download Page {{ $page->page_number }}">
                            <i class="fas fa-download"></i>
                        </a>
                        @endcan
                    </div>
                    @endforeach
                    @endif
                </div>

                <!-- Upload Info -->
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs">Uploaded</p>
                        <p class="text-gray-900 font-medium">{{ $document->uploaded_at->format('d M Y') }}</p>
                    </div>
                    @if($document->uploader)
                    <div>
                        <p class="text-gray-500 text-xs">By</p>
                        <p class="text-gray-900 font-medium truncate">{{ $document->uploader->name }}</p>
                    </div>
                    @endif
                </div>

                @if($document->isVerified())
                <div class="p-2 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-blue-500"></i>
                        <div class="text-xs">
                            <span class="text-blue-700">Verified by {{ $document->verifier->name ?? 'N/A' }}</span>
                            <span class="text-blue-500">on {{ $document->verified_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                @if($document->verification_notes)
                <div class="p-2 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Notes</p>
                    <p class="text-sm text-gray-700">{{ $document->verification_notes }}</p>
                </div>
                @endif

                <!-- Actions -->
                <div class="flex flex-wrap gap-2 pt-2">
                    @can('view', $document)
                    <a href="{{ route('candidates.pre-departure-documents.download', [$candidate, $document]) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                        <i class="fas fa-download mr-1.5"></i>Download
                    </a>
                    @endcan

                    @if(!$document->isVerified())
                        @can('verify', $document)
                        <button type="button"
                                onclick="openVerifyModal{{ $document->id }}()"
                                class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition">
                            <i class="fas fa-check mr-1.5"></i>Verify
                        </button>
                        @endcan

                        @can('reject', $document)
                        <button type="button"
                                onclick="openRejectModal{{ $document->id }}()"
                                class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm rounded-lg transition">
                            <i class="fas fa-times mr-1.5"></i>Reject
                        </button>
                        @endcan
                    @endif

                    @can('delete', $document)
                    <form action="{{ route('candidates.pre-departure-documents.destroy', [$candidate, $document]) }}"
                          method="POST"
                          class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this document?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                            <i class="fas fa-trash mr-1.5"></i>Delete
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            {{-- Verify Modal --}}
            @can('verify', $document)
            <div id="verifyModal{{ $document->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeVerifyModal{{ $document->id }}()"></div>
                    <div class="relative bg-white rounded-xl shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                        <form action="{{ route('candidates.pre-departure-documents.verify', [$candidate, $document]) }}" method="POST">
                            @csrf
                            <div class="px-6 py-4 bg-green-600 rounded-t-xl">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <i class="fas fa-check-circle"></i>Verify Document
                                </h3>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-gray-600 mb-4">Are you sure you want to verify this document?</p>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Verification Notes (optional)</label>
                                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                                <button type="button" onclick="closeVerifyModal{{ $document->id }}()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">Verify Document</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
                function openVerifyModal{{ $document->id }}() {
                    document.getElementById('verifyModal{{ $document->id }}').classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }
                function closeVerifyModal{{ $document->id }}() {
                    document.getElementById('verifyModal{{ $document->id }}').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            </script>
            @endcan

            {{-- Reject Modal --}}
            @can('reject', $document)
            <div id="rejectModal{{ $document->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeRejectModal{{ $document->id }}()"></div>
                    <div class="relative bg-white rounded-xl shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                        <form action="{{ route('candidates.pre-departure-documents.reject', [$candidate, $document]) }}" method="POST">
                            @csrf
                            <div class="px-6 py-4 bg-amber-500 rounded-t-xl">
                                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <i class="fas fa-times-circle"></i>Reject Document
                                </h3>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-gray-600 mb-4">Please provide a reason for rejecting this document.</p>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                                    <textarea name="reason" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                                    <p class="text-xs text-gray-500 mt-1">This reason will be visible to authorized users.</p>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                                <button type="button" onclick="closeRejectModal{{ $document->id }}()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition">Reject Document</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
                function openRejectModal{{ $document->id }}() {
                    document.getElementById('rejectModal{{ $document->id }}').classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }
                function closeRejectModal{{ $document->id }}() {
                    document.getElementById('rejectModal{{ $document->id }}').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            </script>
            @endcan

        @else
            <!-- No Document Uploaded State -->
            <div class="text-center py-4">
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-cloud-upload-alt text-amber-500 text-xl"></i>
                </div>
                <p class="text-gray-500 text-sm mb-2">No document uploaded</p>

                @if($checklist->description)
                <p class="text-xs text-gray-400 mb-4">{{ $checklist->description }}</p>
                @endif

                @can('create', [App\Models\PreDepartureDocument::class, $candidate])
                <form action="{{ route('candidates.pre-departure-documents.store', $candidate) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="text-left">
                    @csrf
                    <input type="hidden" name="document_checklist_id" value="{{ $checklist->id }}">

                    <div class="mb-3">
                        @if($checklist->supports_multiple_pages ?? false)
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Select Files <span class="text-xs text-blue-600">(Multiple pages supported)</span>
                        </label>
                        <div class="relative">
                            <input type="file"
                                   name="files[]"
                                   id="files{{ $checklist->id }}"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   multiple
                                   required
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG (Max: 5MB each, up to {{ $checklist->max_pages ?? 5 }} files)</p>
                        @else
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select File</label>
                        <div class="relative">
                            <input type="file"
                                   name="file"
                                   id="file{{ $checklist->id }}"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   required
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG (Max: 5MB)</p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <textarea name="notes"
                                  rows="2"
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Add any relevant notes..."></textarea>
                    </div>

                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        <i class="fas fa-upload"></i>
                        Upload Document
                    </button>
                </form>
                @else
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-100">
                    <p class="text-xs text-amber-700">
                        <i class="fas fa-lock mr-1"></i>You do not have permission to upload documents.
                    </p>
                </div>
                @endcan
            </div>
        @endif
    </div>
</div>
