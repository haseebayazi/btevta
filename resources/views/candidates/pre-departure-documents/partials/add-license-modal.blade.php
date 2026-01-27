<div class="modal fade" id="addLicenseModal" tabindex="-1" role="dialog" aria-labelledby="addLicenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('candidates.licenses.store', $candidate) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addLicenseModalLabel">
                        <i class="fas fa-id-card"></i> Add License
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="license_type">License Type <span class="text-danger">*</span></label>
                                <select class="form-control" id="license_type" name="license_type" required>
                                    <option value="">Select Type</option>
                                    <option value="driving">Driving License</option>
                                    <option value="professional">Professional License</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="license_name">License Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="license_name" 
                                       name="license_name" 
                                       placeholder="e.g., Car License, RN License"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="license_number">License Number <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="license_number" 
                                       name="license_number" 
                                       placeholder="e.g., ABC123456"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="license_category">Category</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="license_category" 
                                       name="license_category" 
                                       placeholder="e.g., B, C, D">
                                <small class="form-text text-muted">For driving licenses (B, C, D, etc.)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="issuing_authority">Issuing Authority</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="issuing_authority" 
                                       name="issuing_authority" 
                                       placeholder="e.g., Pakistan Driving License Authority">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="issue_date">Issue Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="issue_date" 
                                       name="issue_date"
                                       max="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="expiry_date" 
                                       name="expiry_date"
                                       min="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="file">License Copy (optional)</label>
                                <input type="file" 
                                       class="form-control-file" 
                                       id="file" 
                                       name="file"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <small class="form-text text-muted">PDF, JPG, PNG (Max: 5MB)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save License
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide category field based on license type
    $('#license_type').on('change', function() {
        if ($(this).val() === 'driving') {
            $('#license_category').closest('.form-group').show();
        } else {
            $('#license_category').closest('.form-group').hide();
            $('#license_category').val('');
        }
    });
});
</script>
@endpush
