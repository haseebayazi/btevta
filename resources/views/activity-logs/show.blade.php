@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('admin.activity-logs') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Activity Logs
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Activity Log Details #{{ $activity->id }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong>Date/Time:</strong></div>
                <div class="col-md-9">{{ $activity->created_at->format('M d, Y H:i:s') }} ({{ $activity->created_at->diffForHumans() }})</div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3"><strong>User:</strong></div>
                <div class="col-md-9">
                    @if($activity->causer)
                        {{ $activity->causer->name }} ({{ $activity->causer->role }})
                    @else
                        System
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3"><strong>Action:</strong></div>
                <div class="col-md-9">
                    <span class="badge bg-primary">{{ $activity->description }}</span>
                </div>
            </div>

            @if($activity->log_name)
            <div class="row mb-3">
                <div class="col-md-3"><strong>Log Name:</strong></div>
                <div class="col-md-9">{{ $activity->log_name }}</div>
            </div>
            @endif

            @if($activity->subject)
            <div class="row mb-3">
                <div class="col-md-3"><strong>Subject:</strong></div>
                <div class="col-md-9">
                    {{ class_basename($activity->subject_type) }} (ID: {{ $activity->subject_id }})
                </div>
            </div>
            @endif

            @if($activity->properties && count($activity->properties) > 0)
            <div class="row mb-3">
                <div class="col-md-3"><strong>Properties:</strong></div>
                <div class="col-md-9">
                    <pre class="bg-light p-3 rounded"><code>{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
