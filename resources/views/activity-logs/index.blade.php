@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-history"></i> Activity Logs</h2>
            <p class="text-muted">View system activity and audit trail</p>
        </div>
        <div>
            <a href="{{ route('admin.activity-logs.statistics') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Statistics
            </a>
            <a href="{{ route('admin.activity-logs.export', request()->all()) }}" class="btn btn-success">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.activity-logs') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               value="{{ request('search') }}"
                               placeholder="Search description...">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Log Type</label>
                        <select name="log_name" class="form-select">
                            <option value="">All Types</option>
                            @foreach($logNames as $logName)
                                <option value="{{ $logName }}" {{ request('log_name') == $logName ? 'selected' : '' }}>
                                    {{ $logName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Subject Type</label>
                        <select name="subject_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($subjectTypes as $type)
                                <option value="{{ $type['value'] }}" {{ request('subject_type') == $type['value'] ? 'selected' : '' }}>
                                    {{ $type['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">User</label>
                        <select name="causer_id" class="form-select">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control"
                               value="{{ request('from_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control"
                               value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.activity-logs') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Subject</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity->id }}</td>
                            <td>
                                <small>{{ $activity->created_at->format('M d, Y') }}</small><br>
                                <small class="text-muted">{{ $activity->created_at->format('H:i:s') }}</small>
                            </td>
                            <td>
                                @if($activity->causer)
                                    <strong>{{ $activity->causer->name }}</strong><br>
                                    <small class="text-muted">{{ $activity->causer->role }}</small>
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $activity->description }}</span>
                                @if($activity->log_name)
                                    <br><small class="text-muted">{{ $activity->log_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($activity->subject)
                                    <strong>{{ class_basename($activity->subject_type) }}</strong><br>
                                    <small class="text-muted">ID: {{ $activity->subject_id }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.activity-logs.show', $activity) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No activity logs found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $activities->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

    <!-- Cleanup Section (Super Admin Only) -->
    @if(auth()->user()->role === 'super_admin')
    <div class="card mt-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-trash"></i> Cleanup Old Logs</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.activity-logs.clean') }}"
                  onsubmit="return confirm('Are you sure you want to delete old activity logs? This action cannot be undone.');">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Delete logs older than (days):</label>
                        <input type="number" name="days" class="form-control" value="90" min="30" max="365" required>
                        <small class="text-muted">Minimum: 30 days, Maximum: 365 days</small>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Old Logs
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
