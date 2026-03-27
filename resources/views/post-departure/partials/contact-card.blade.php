<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-phone"></i> Foreign Contact Details</h6>
    </div>
    <div class="card-body">
        @if($detail->foreign_mobile_number)
        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Mobile Number:</strong>
                <p>{{ $detail->foreign_mobile_number }}</p>
            </div>
            <div class="col-md-4">
                <strong>Carrier:</strong>
                <p>{{ $detail->foreign_mobile_carrier ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <strong>Address:</strong>
                <p>{{ $detail->foreign_address ?? 'N/A' }}</p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('post-departure.update-contact', $detail) }}">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror"
                               value="{{ old('mobile_number', $detail->foreign_mobile_number) }}" required>
                        @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Carrier</label>
                        <input type="text" name="carrier" class="form-control @error('carrier') is-invalid @enderror"
                               value="{{ old('carrier', $detail->foreign_mobile_carrier) }}">
                        @error('carrier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                  rows="2">{{ old('address', $detail->foreign_address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update Contact</button>
        </form>
    </div>
</div>
