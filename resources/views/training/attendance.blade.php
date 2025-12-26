@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('training.index') }}">Training</a></li>
                    <li class="breadcrumb-item active">Mark Attendance</li>
                </ol>
            </nav>
            <h2 class="mb-0">Mark Attendance</h2>
            <p class="text-muted mb-0">Record daily attendance for training batches</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('training.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Training
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

    {{-- Batch Selection --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Select Batch & Date</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('training.attendance-form') }}" method="GET" class="row align-items-end">
                <div class="col-md-5">
                    <label class="font-weight-bold">Select Batch</label>
                    <select name="batch_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Select a Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }} ({{ $batch->batch_code }}) - {{ $batch->candidates->count() }} candidates
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold">Attendance Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ date('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sync-alt"></i> Load
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance Form --}}
    @if($selectedBatch)
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-clipboard-check mr-2"></i>{{ $selectedBatch->name }}</h5>
                        <small>Date: {{ \Carbon\Carbon::parse($date)->format('d M Y, l') }}</small>
                    </div>
                    <div>
                        <span class="badge badge-light">{{ $selectedBatch->candidates->count() }} Candidates</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($selectedBatch->candidates->count() > 0)
                    <form action="{{ route('training.bulk-attendance') }}" method="POST" id="attendanceForm">
                        @csrf
                        <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                        <input type="hidden" name="date" value="{{ $date }}">

                        {{-- Quick Actions --}}
                        <div class="d-flex justify-content-between mb-4 pb-3 border-bottom">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-success" onclick="markAll('present')">
                                    <i class="fas fa-check-double"></i> All Present
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="markAll('absent')">
                                    <i class="fas fa-times-circle"></i> All Absent
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearAll()">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                            </div>
                            <div class="text-muted">
                                Total: <strong id="totalCount">0</strong> |
                                Present: <strong id="presentCount" class="text-success">0</strong> |
                                Absent: <strong id="absentCount" class="text-danger">0</strong> |
                                Leave: <strong id="leaveCount" class="text-warning">0</strong>
                            </div>
                        </div>

                        {{-- Candidates List --}}
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">BTEVTA ID</th>
                                        <th width="25%">Name</th>
                                        <th width="30%">Attendance Status</th>
                                        <th width="25%">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedBatch->candidates as $index => $candidate)
                                        @php
                                            $existingAttendance = $candidate->attendances->first();
                                            $currentStatus = $existingAttendance ? $existingAttendance->status : null;
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="text-monospace">{{ $candidate->btevta_id }}</td>
                                            <td>
                                                <strong>{{ $candidate->name }}</strong>
                                                <small class="text-muted d-block">{{ $candidate->trade->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                    <label class="btn btn-outline-success {{ $currentStatus === 'present' ? 'active' : '' }}">
                                                        <input type="radio" name="attendances[{{ $index }}][status]" value="present" autocomplete="off" class="status-radio"
                                                               {{ $currentStatus === 'present' ? 'checked' : '' }} data-candidate="{{ $index }}">
                                                        <i class="fas fa-check"></i> Present
                                                    </label>
                                                    <label class="btn btn-outline-danger {{ $currentStatus === 'absent' ? 'active' : '' }}">
                                                        <input type="radio" name="attendances[{{ $index }}][status]" value="absent" autocomplete="off" class="status-radio"
                                                               {{ $currentStatus === 'absent' ? 'checked' : '' }} data-candidate="{{ $index }}">
                                                        <i class="fas fa-times"></i> Absent
                                                    </label>
                                                    <label class="btn btn-outline-warning {{ $currentStatus === 'leave' ? 'active' : '' }}">
                                                        <input type="radio" name="attendances[{{ $index }}][status]" value="leave" autocomplete="off" class="status-radio"
                                                               {{ $currentStatus === 'leave' ? 'checked' : '' }} data-candidate="{{ $index }}">
                                                        <i class="fas fa-clock"></i> Leave
                                                    </label>
                                                </div>
                                                <input type="hidden" name="attendances[{{ $index }}][candidate_id]" value="{{ $candidate->id }}">
                                            </td>
                                            <td>
                                                <input type="text" name="attendances[{{ $index }}][remarks]" class="form-control form-control-sm"
                                                       placeholder="Optional remarks" value="{{ $existingAttendance->remarks ?? '' }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-between pt-4 border-top">
                            <a href="{{ route('training.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>No candidates enrolled in this batch.
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Select a Batch</h4>
                <p class="text-muted">Please select a batch from the dropdown above to mark attendance.</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Mark all candidates with a specific status
    function markAll(status) {
        document.querySelectorAll('.status-radio').forEach(radio => {
            if (radio.value === status) {
                radio.checked = true;
                radio.closest('.btn').classList.add('active');
            } else {
                radio.closest('.btn').classList.remove('active');
            }
        });
        updateCounts();
    }

    // Clear all selections
    function clearAll() {
        document.querySelectorAll('.status-radio').forEach(radio => {
            radio.checked = false;
            radio.closest('.btn').classList.remove('active');
        });
        updateCounts();
    }

    // Update attendance counts
    function updateCounts() {
        const total = document.querySelectorAll('input[type="hidden"][name$="[candidate_id]"]').length;
        const present = document.querySelectorAll('.status-radio[value="present"]:checked').length;
        const absent = document.querySelectorAll('.status-radio[value="absent"]:checked').length;
        const leave = document.querySelectorAll('.status-radio[value="leave"]:checked').length;

        document.getElementById('totalCount').textContent = total;
        document.getElementById('presentCount').textContent = present;
        document.getElementById('absentCount').textContent = absent;
        document.getElementById('leaveCount').textContent = leave;
    }

    // Update counts on radio change
    document.querySelectorAll('.status-radio').forEach(radio => {
        radio.addEventListener('change', updateCounts);
    });

    // Initial count
    document.addEventListener('DOMContentLoaded', updateCounts);

    // Form validation
    document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
        const checkedRadios = document.querySelectorAll('.status-radio:checked');
        if (checkedRadios.length === 0) {
            e.preventDefault();
            alert('Please mark attendance for at least one candidate.');
            return false;
        }
    });
</script>
@endpush
@endsection
