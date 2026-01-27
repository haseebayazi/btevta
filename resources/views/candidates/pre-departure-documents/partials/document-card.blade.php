<div class="card h-100 {{ $document ? 'border-success' : 'border-warning' }}">
    <div class="card-header {{ $document ? 'bg-success text-white' : 'bg-warning text-dark' }}">
        <div class="d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-{{ $document ? 'check-circle' : 'exclamation-circle' }}"></i>
                {{ $checklist->name }}
                @if($checklist->is_mandatory)
                    <span class="badge badge-danger badge-sm">Required</span>
                @endif
            </span>
            @if($document && $document->isVerified())
                <span class="badge badge-light">
                    <i class="fas fa-shield-alt"></i> Verified
                </span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($document)
            {{-- Document exists --}}
            <div class="mb-2">
                <small class="text-muted">File:</small>
                <div>{{ $document->original_filename }}</div>
            </div>
            <div class="mb-2">
                <small class="text-muted">Size:</small>
                <div>{{ number_format($document->file_size / 1024, 2) }} KB</div>
            </div>
            <div class="mb-2">
                <small class="text-muted">Uploaded:</small>
                <div>{{ $document->uploaded_at->format('M d, Y g:i A') }}</div>
            </div>
            @if($document->uploader)
            <div class="mb-2">
                <small class="text-muted">By:</small>
                <div>{{ $document->uploader->name }}</div>
            </div>
            @endif

            @if($document->isVerified())
            <div class="mb-2">
                <small class="text-muted">Verified By:</small>
                <div>{{ $document->verifier->name ?? 'N/A' }} on {{ $document->verified_at->format('M d, Y') }}</div>
            </div>
            @endif

            @if($document->verification_notes)
            <div class="mb-2">
                <small class="text-muted">Notes:</small>
                <div class="text-sm">{{ $document->verification_notes }}</div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="btn-group btn-group-sm w-100 mt-2" role="group">
                @can('view', $document)
                <a href="{{ route('candidates.pre-departure-documents.download', [$candidate, $document]) }}" 
                   class="btn btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
                @endcan

                @if(!$document->isVerified())
                    @can('verify', $document)
                    <button type="button" 
                            class="btn btn-success" 
                            data-toggle="modal" 
                            data-target="#verifyModal{{ $document->id }}">
                        <i class="fas fa-check"></i> Verify
                    </button>
                    @endcan

                    @can('reject', $document)
                    <button type="button" 
                            class="btn btn-warning" 
                            data-toggle="modal" 
                            data-target="#rejectModal{{ $document->id }}">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    @endcan
                @endif

                @can('delete', $document)
                <form action="{{ route('candidates.pre-departure-documents.destroy', [$candidate, $document]) }}" 
                      method="POST" 
                      class="d-inline"
                      onsubmit="return confirm('Are you sure you want to delete this document?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endcan
            </div>

            {{-- Verify Modal --}}
            @can('verify', $document)
            <div class="modal fade" id="verifyModal{{ $document->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('candidates.pre-departure-documents.verify', [$candidate, $document]) }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">Verify Document</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to verify this document?</p>
                                <div class="form-group">
                                    <label>Verification Notes (optional)</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Verify Document</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            {{-- Reject Modal --}}
            @can('reject', $document)
            <div class="modal fade" id="rejectModal{{ $document->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('candidates.pre-departure-documents.reject', [$candidate, $document]) }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title">Reject Document</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Please provide a reason for rejecting this document.</p>
                                <div class="form-group">
                                    <label>Reason for Rejection <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                                    <small class="form-text text-muted">This reason will be visible to the candidate.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning">Reject Document</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

        @else
            {{-- No document uploaded --}}
            <p class="text-muted mb-3">
                <i class="fas fa-upload"></i> No document uploaded yet.
            </p>

            @if($checklist->description)
            <div class="alert alert-info alert-sm mb-3">
                <small>{{ $checklist->description }}</small>
            </div>
            @endif

            @can('create', [App\Models\PreDepartureDocument::class, $candidate])
            <form action="{{ route('candidates.pre-departure-documents.store', $candidate) }}" 
                  method="POST" 
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="document_checklist_id" value="{{ $checklist->id }}">
                
                <div class="form-group">
                    <label for="file{{ $checklist->id }}">Upload File</label>
                    <input type="file" 
                           class="form-control-file" 
                           id="file{{ $checklist->id }}" 
                           name="file" 
                           accept=".pdf,.jpg,.jpeg,.png"
                           required>
                    <small class="form-text text-muted">
                        Accepted formats: PDF, JPG, PNG (Max: 5MB)
                    </small>
                </div>

                <div class="form-group">
                    <label for="notes{{ $checklist->id }}">Notes (optional)</label>
                    <textarea class="form-control" 
                              id="notes{{ $checklist->id }}" 
                              name="notes" 
                              rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </form>
            @else
            <div class="alert alert-warning alert-sm">
                <small>You do not have permission to upload documents.</small>
            </div>
            @endcan
        @endif
    </div>
</div>
