<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card"></i> Iqama / Residency</h6>
    </div>
    <div class="card-body">
        @if($detail->iqama_number)
        <div class="row mb-3">
            <div class="col-md-3">
                <strong>Iqama Number:</strong>
                <p>{{ $detail->iqama_number }}</p>
            </div>
            <div class="col-md-3">
                <strong>Issue Date:</strong>
                <p>{{ $detail->iqama_issue_date?->format('d M Y') ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Expiry Date:</strong>
                <p>
                    {{ $detail->iqama_expiry_date?->format('d M Y') ?? 'N/A' }}
                    @if($detail->iqama_expiring)
                        <span class="badge badge-warning">Expiring Soon</span>
                    @endif
                </p>
            </div>
            <div class="col-md-3">
                <strong>Status:</strong>
                <p><span class="badge badge-{{ $detail->iqama_status?->color() ?? 'secondary' }}">{{ $detail->iqama_status?->label() ?? 'N/A' }}</span></p>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('post-departure.update-iqama', $detail) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Iqama Number <span class="text-danger">*</span></label>
                        <input type="text" name="iqama_number" class="form-control @error('iqama_number') is-invalid @enderror"
                               value="{{ old('iqama_number', $detail->iqama_number) }}" required>
                        @error('iqama_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="iqama_issue_date" class="form-control @error('iqama_issue_date') is-invalid @enderror"
                               value="{{ old('iqama_issue_date', $detail->iqama_issue_date?->format('Y-m-d')) }}" required>
                        @error('iqama_issue_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Expiry Date <span class="text-danger">*</span></label>
                        <input type="date" name="iqama_expiry_date" class="form-control @error('iqama_expiry_date') is-invalid @enderror"
                               value="{{ old('iqama_expiry_date', $detail->iqama_expiry_date?->format('Y-m-d')) }}" required>
                        @error('iqama_expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="iqama_status" class="form-control @error('iqama_status') is-invalid @enderror" required>
                            @foreach(\App\Enums\IqamaStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('iqama_status', $detail->iqama_status?->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('iqama_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Evidence</label>
                        <input type="file" name="evidence" class="form-control-file @error('evidence') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @error('evidence') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update Iqama</button>
        </form>
    </div>
</div>
