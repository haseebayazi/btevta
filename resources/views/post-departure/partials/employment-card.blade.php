<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-briefcase"></i> Employment History</h6>
    </div>
    <div class="card-body">
        @if($employmentHistory->isNotEmpty())
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Company</th>
                        <th>Position</th>
                        <th>Salary (Total)</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employmentHistory as $employment)
                    <tr>
                        <td>{{ $employment->sequence_label }}</td>
                        <td>{{ $employment->company_name }}</td>
                        <td>{{ $employment->position_title ?? 'N/A' }}</td>
                        <td>{{ number_format($employment->total_package, 2) }} {{ $employment->salary_currency ?? 'SAR' }}</td>
                        <td>{{ $employment->commencement_date?->format('d M Y') ?? 'N/A' }}</td>
                        <td>{{ $employment->end_date?->format('d M Y') ?? 'Ongoing' }}</td>
                        <td><span class="badge badge-{{ $employment->status?->color() ?? 'secondary' }}">{{ $employment->status?->label() ?? 'N/A' }}</span></td>
                        <td>{{ $employment->employment_duration ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted">No employment records yet.</p>
        @endif

        <!-- Add Employment Form -->
        @if($employmentHistory->isEmpty())
        <h6 class="font-weight-bold mb-3">Record Initial Employment</h6>
        <form method="POST" action="{{ route('post-departure.add-employment', $detail) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                               value="{{ old('company_name') }}" required>
                        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Position Title</label>
                        <input type="text" name="position_title" class="form-control @error('position_title') is-invalid @enderror"
                               value="{{ old('position_title') }}">
                        @error('position_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Work Location</label>
                        <input type="text" name="work_location" class="form-control @error('work_location') is-invalid @enderror"
                               value="{{ old('work_location') }}">
                        @error('work_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Base Salary <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="base_salary" class="form-control @error('base_salary') is-invalid @enderror"
                               value="{{ old('base_salary') }}" required>
                        @error('base_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', 'SAR') }}" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Housing Allowance</label>
                        <input type="number" step="0.01" name="housing_allowance" class="form-control"
                               value="{{ old('housing_allowance') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Food Allowance</label>
                        <input type="number" step="0.01" name="food_allowance" class="form-control"
                               value="{{ old('food_allowance') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Transport Allowance</label>
                        <input type="number" step="0.01" name="transport_allowance" class="form-control"
                               value="{{ old('transport_allowance') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Commencement <span class="text-danger">*</span></label>
                        <input type="date" name="commencement_date" class="form-control @error('commencement_date') is-invalid @enderror"
                               value="{{ old('commencement_date') }}" required>
                        @error('commencement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Employment Contract (PDF)</label>
                        <input type="file" name="contract" class="form-control-file @error('contract') is-invalid @enderror"
                               accept=".pdf">
                        @error('contract') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Record Employment</button>
        </form>
        @endif
    </div>
</div>
