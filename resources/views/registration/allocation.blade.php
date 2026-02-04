@extends('layouts.app')
@section('title', 'Registration Allocation - ' . $candidate->name)
@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('registration.index') }}">Registration</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('registration.show', $candidate->id) }}">{{ $candidate->btevta_id }}</a></li>
                    <li class="breadcrumb-item active">Allocation</li>
                </ol>
            </nav>
            <h2 class="mb-0">Registration Allocation</h2>
            <p class="text-muted mb-0">{{ $candidate->name }} ({{ $candidate->btevta_id }})</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('registration.show', $candidate->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('registration.store-allocation', $candidate->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            {{-- Left Column --}}
            <div class="col-lg-8">
                {{-- Candidate Info Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Candidate Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <strong class="text-muted">TheLeap ID:</strong><br>
                                <span class="text-monospace font-weight-bold">{{ $candidate->btevta_id }}</span>
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong class="text-muted">CNIC:</strong><br>
                                <span class="text-monospace">{{ $candidate->cnic ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong class="text-muted">Status:</strong><br>
                                <span class="badge badge-info px-3 py-2">
                                    {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4 mb-2">
                                <strong class="text-muted">Phone:</strong><br>{{ $candidate->phone ?? 'N/A' }}
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong class="text-muted">Email:</strong><br>{{ $candidate->email ?? 'N/A' }}
                            </div>
                            <div class="col-md-4 mb-2">
                                <strong class="text-muted">Father Name:</strong><br>{{ $candidate->father_name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Allocation Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Allocation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="campus_id" class="font-weight-bold">Campus <span class="text-danger">*</span></label>
                                <select name="campus_id" id="campus_id" class="form-control @error('campus_id') is-invalid @enderror" required>
                                    <option value="">-- Select Campus --</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->id }}" {{ old('campus_id', $candidate->campus_id) == $campus->id ? 'selected' : '' }}>
                                            {{ $campus->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('campus_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="program_id" class="font-weight-bold">Program <span class="text-danger">*</span></label>
                                <select name="program_id" id="program_id" class="form-control @error('program_id') is-invalid @enderror" required>
                                    <option value="">-- Select Program --</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" {{ old('program_id', $candidate->program_id) == $program->id ? 'selected' : '' }}>
                                            {{ $program->name }} ({{ $program->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('program_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="trade_id" class="font-weight-bold">Trade <span class="text-danger">*</span></label>
                                <select name="trade_id" id="trade_id" class="form-control @error('trade_id') is-invalid @enderror" required>
                                    <option value="">-- Select Trade --</option>
                                    @foreach($trades as $trade)
                                        <option value="{{ $trade->id }}" {{ old('trade_id', $candidate->trade_id) == $trade->id ? 'selected' : '' }}>
                                            {{ $trade->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('trade_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="oep_id" class="font-weight-bold">OEP (Overseas Employment Promoter)</label>
                                <select name="oep_id" id="oep_id" class="form-control @error('oep_id') is-invalid @enderror">
                                    <option value="">-- Select OEP --</option>
                                    @foreach($oeps as $oep)
                                        <option value="{{ $oep->id }}" {{ old('oep_id', $candidate->oep_id) == $oep->id ? 'selected' : '' }}>
                                            {{ $oep->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('oep_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="implementing_partner_id" class="font-weight-bold">Implementing Partner</label>
                                <select name="implementing_partner_id" id="implementing_partner_id" class="form-control @error('implementing_partner_id') is-invalid @enderror">
                                    <option value="">-- Select Implementing Partner --</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ old('implementing_partner_id', $candidate->implementing_partner_id) == $partner->id ? 'selected' : '' }}>
                                            {{ $partner->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('implementing_partner_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info mt-2">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Auto-Batch:</strong> A batch will be automatically assigned based on Campus + Program + Trade combination.
                            Current batch size: <strong>{{ config('wasl.batch_size', 25) }}</strong> candidates.
                        </div>
                    </div>
                </div>

                {{-- Course Assignment Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="mb-0"><i class="fas fa-book mr-2"></i>Course Assignment</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="course_id" class="font-weight-bold">Course <span class="text-danger">*</span></label>
                                <select name="course_id" id="course_id" class="form-control @error('course_id') is-invalid @enderror" required>
                                    <option value="">-- Select Course --</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" 
                                            data-duration="{{ $course->duration_days }}"
                                            data-type="{{ $course->training_type }}"
                                            {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->name }} ({{ $course->duration_days }} days - {{ ucfirst(str_replace('_', ' ', $course->training_type)) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="course_start_date" class="font-weight-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="course_start_date" id="course_start_date" 
                                    class="form-control @error('course_start_date') is-invalid @enderror"
                                    value="{{ old('course_start_date', now()->format('Y-m-d')) }}"
                                    min="{{ now()->format('Y-m-d') }}" required>
                                @error('course_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="course_end_date" class="font-weight-bold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="course_end_date" id="course_end_date" 
                                    class="form-control @error('course_end_date') is-invalid @enderror"
                                    value="{{ old('course_end_date') }}" required>
                                @error('course_end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Next of Kin Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Next of Kin (Financial Account)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nok_name" class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="nok_name" id="nok_name" 
                                    class="form-control @error('nok_name') is-invalid @enderror"
                                    value="{{ old('nok_name', $candidate->nextOfKin?->name) }}" required>
                                @error('nok_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nok_relationship" class="font-weight-bold">Relationship <span class="text-danger">*</span></label>
                                <select name="nok_relationship" id="nok_relationship" class="form-control @error('nok_relationship') is-invalid @enderror" required>
                                    <option value="">-- Select Relationship --</option>
                                    @foreach($relationships as $key => $label)
                                        <option value="{{ $key }}" {{ old('nok_relationship', $candidate->nextOfKin?->relationship) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nok_relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nok_cnic" class="font-weight-bold">CNIC (13 digits) <span class="text-danger">*</span></label>
                                <input type="text" name="nok_cnic" id="nok_cnic" 
                                    class="form-control @error('nok_cnic') is-invalid @enderror"
                                    value="{{ old('nok_cnic', $candidate->nextOfKin?->cnic) }}"
                                    maxlength="13" pattern="[0-9]{13}" placeholder="1234567890123" required>
                                @error('nok_cnic')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nok_phone" class="font-weight-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="nok_phone" id="nok_phone" 
                                    class="form-control @error('nok_phone') is-invalid @enderror"
                                    value="{{ old('nok_phone', $candidate->nextOfKin?->phone) }}" required>
                                @error('nok_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="nok_address" class="font-weight-bold">Address</label>
                                <textarea name="nok_address" id="nok_address" rows="2" 
                                    class="form-control @error('nok_address') is-invalid @enderror">{{ old('nok_address', $candidate->nextOfKin?->address) }}</textarea>
                                @error('nok_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-money-bill-wave mr-2"></i>Financial Account Details (for remittance)</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nok_payment_method_id" class="font-weight-bold">Payment Method <span class="text-danger">*</span></label>
                                <select name="nok_payment_method_id" id="nok_payment_method_id" class="form-control @error('nok_payment_method_id') is-invalid @enderror" required>
                                    <option value="">-- Select Payment Method --</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" 
                                            data-requires-bank="{{ $method->requires_bank_name ? 'true' : 'false' }}"
                                            {{ old('nok_payment_method_id', $candidate->nextOfKin?->payment_method_id) == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nok_payment_method_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nok_account_number" class="font-weight-bold">Account Number <span class="text-danger">*</span></label>
                                <input type="text" name="nok_account_number" id="nok_account_number" 
                                    class="form-control @error('nok_account_number') is-invalid @enderror"
                                    value="{{ old('nok_account_number', $candidate->nextOfKin?->account_number) }}" required>
                                @error('nok_account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3" id="bank_name_group" style="display: none;">
                                <label for="nok_bank_name" class="font-weight-bold">Bank Name <span class="text-danger">*</span></label>
                                <input type="text" name="nok_bank_name" id="nok_bank_name" 
                                    class="form-control @error('nok_bank_name') is-invalid @enderror"
                                    value="{{ old('nok_bank_name', $candidate->nextOfKin?->bank_name) }}">
                                @error('nok_bank_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nok_id_card" class="font-weight-bold">ID Card Copy (CNIC)</label>
                                <input type="file" name="nok_id_card" id="nok_id_card" 
                                    class="form-control-file @error('nok_id_card') is-invalid @enderror"
                                    accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">PDF, JPG, JPEG, PNG - Max 5MB</small>
                                @error('nok_id_card')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($candidate->nextOfKin?->id_card_path)
                                    <div class="mt-2">
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>ID Card Already Uploaded</span>
                                        <a href="{{ $candidate->nextOfKin->id_card_url }}" target="_blank" class="btn btn-sm btn-outline-info ml-2">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-4">
                {{-- Summary Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Registration Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Candidate</span>
                                <strong>{{ $candidate->name }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>TheLeap ID</span>
                                <strong class="text-monospace">{{ $candidate->btevta_id }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Current Status</span>
                                <span class="badge badge-info">{{ ucfirst($candidate->status) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>New Status</span>
                                <span class="badge badge-success">Registered</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Batch Info Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-layer-group mr-2"></i>Auto-Batch Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Upon registration, the candidate will be automatically assigned to a batch based on:
                        </p>
                        <ul class="small">
                            <li>Selected Campus</li>
                            <li>Selected Program</li>
                            <li>Selected Trade</li>
                        </ul>
                        <p class="text-muted small mb-0">
                            Batch size is configured to <strong>{{ config('wasl.batch_size', 25) }}</strong> candidates.
                            Available sizes: {{ implode(', ', config('wasl.allowed_batch_sizes', [20, 25, 30])) }}.
                        </p>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-check-circle mr-2"></i>Complete Registration
                        </button>
                        <a href="{{ route('registration.show', $candidate->id) }}" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Course end date calculation
    const courseSelect = document.getElementById('course_id');
    const startDateInput = document.getElementById('course_start_date');
    const endDateInput = document.getElementById('course_end_date');
    
    function updateEndDate() {
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        const duration = parseInt(selectedOption.getAttribute('data-duration')) || 0;
        const startDate = startDateInput.value;
        
        if (startDate && duration > 0) {
            const start = new Date(startDate);
            start.setDate(start.getDate() + duration);
            endDateInput.value = start.toISOString().split('T')[0];
        }
    }
    
    courseSelect.addEventListener('change', updateEndDate);
    startDateInput.addEventListener('change', updateEndDate);
    
    // Payment method bank name toggle
    const paymentMethodSelect = document.getElementById('nok_payment_method_id');
    const bankNameGroup = document.getElementById('bank_name_group');
    const bankNameInput = document.getElementById('nok_bank_name');
    
    function toggleBankName() {
        const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
        const requiresBank = selectedOption.getAttribute('data-requires-bank') === 'true';
        
        if (requiresBank) {
            bankNameGroup.style.display = 'block';
            bankNameInput.setAttribute('required', 'required');
        } else {
            bankNameGroup.style.display = 'none';
            bankNameInput.removeAttribute('required');
        }
    }
    
    paymentMethodSelect.addEventListener('change', toggleBankName);
    
    // Initialize on page load
    toggleBankName();
    
    // CNIC validation
    const cnicInput = document.getElementById('nok_cnic');
    cnicInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 13);
    });
});
</script>
@endpush
@endsection
