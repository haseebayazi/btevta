<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-exchange-alt"></i> Company Switches</h6>
    </div>
    <div class="card-body">
        @if($switches->isNotEmpty())
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>Switch #</th>
                        <th>From Company</th>
                        <th>To Company</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($switches as $switch)
                    <tr>
                        <td>{{ $switch->switch_number }}</td>
                        <td>{{ $switch->fromEmployment?->company_name ?? 'N/A' }}</td>
                        <td>{{ $switch->toEmployment?->company_name ?? 'N/A' }}</td>
                        <td>{{ Str::limit($switch->reason, 50) }}</td>
                        <td>{{ $switch->switch_date->format('d M Y') }}</td>
                        <td><span class="badge badge-{{ $switch->status->color() }}">{{ $switch->status->label() }}</span></td>
                        <td>
                            @if($switch->status->value === 'pending')
                                @can('approve', $switch)
                                <form method="POST" action="{{ route('post-departure.approve-switch', $switch) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endcan
                            @elseif($switch->status->value === 'approved')
                                @can('complete', $switch)
                                <form method="POST" action="{{ route('post-departure.complete-switch', $switch) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" title="Complete">
                                        <i class="fas fa-flag-checkered"></i>
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @php
            $currentEmployment = $detail->currentEmployment;
            $completedSwitches = $switches->filter(fn($s) => in_array($s->status->value, ['approved', 'completed']))->count();
        @endphp

        @if($currentEmployment && $completedSwitches < 2)
        <h6 class="font-weight-bold mb-3">Initiate Company Switch</h6>
        <form method="POST" action="{{ route('post-departure.initiate-switch', $detail) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>New Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                               value="{{ old('company_name') }}" required>
                        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Reason for Switch <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                                  rows="2" required>{{ old('reason') }}</textarea>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>New Salary <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="base_salary" class="form-control @error('base_salary') is-invalid @enderror"
                               value="{{ old('base_salary') }}" required>
                        @error('base_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                        <label>Release Letter (PDF) <span class="text-danger">*</span></label>
                        <input type="file" name="release_letter" class="form-control-file @error('release_letter') is-invalid @enderror"
                               accept=".pdf" required>
                        @error('release_letter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>New Contract (PDF)</label>
                        <input type="file" name="new_contract" class="form-control-file @error('new_contract') is-invalid @enderror"
                               accept=".pdf">
                        @error('new_contract') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-exchange-alt"></i> Initiate Switch</button>
        </form>
        @elseif($completedSwitches >= 2)
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle"></i> Maximum of 2 company switches has been reached.
        </div>
        @elseif(!$currentEmployment)
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle"></i> Record initial employment before initiating a company switch.
        </div>
        @endif
    </div>
</div>
