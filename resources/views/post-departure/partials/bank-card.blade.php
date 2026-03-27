<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-university"></i> Foreign Bank Account</h6>
    </div>
    <div class="card-body">
        @if($detail->foreign_bank_account)
        <div class="row mb-3">
            <div class="col-md-3">
                <strong>Bank Name:</strong>
                <p>{{ $detail->foreign_bank_name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Account Number:</strong>
                <p>{{ $detail->foreign_bank_account }}</p>
            </div>
            <div class="col-md-3">
                <strong>IBAN:</strong>
                <p>{{ $detail->foreign_bank_iban ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>SWIFT:</strong>
                <p>{{ $detail->foreign_bank_swift ?? 'N/A' }}</p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('post-departure.update-bank', $detail) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                               value="{{ old('bank_name', $detail->foreign_bank_name) }}" required>
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror"
                               value="{{ old('account_number', $detail->foreign_bank_account) }}" required>
                        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>IBAN</label>
                        <input type="text" name="iban" class="form-control @error('iban') is-invalid @enderror"
                               value="{{ old('iban', $detail->foreign_bank_iban) }}">
                        @error('iban') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>SWIFT</label>
                        <input type="text" name="swift" class="form-control @error('swift') is-invalid @enderror"
                               value="{{ old('swift', $detail->foreign_bank_swift) }}">
                        @error('swift') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Evidence</label>
                        <input type="file" name="evidence" class="form-control-file @error('evidence') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @error('evidence') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update Bank Details</button>
        </form>
    </div>
</div>
