@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h3 class="mb-4">
                <i class="fas fa-history"></i> 
                Activity Timeline for {{ $candidate->name }}
            </h3>

            @if($activities->count() > 0)
                <div class="timeline">
                    @foreach($activities as $activity)
                        <div class="timeline-item mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6 class="card-title">{{ $activity->description }}</h6>
                                            <p class="card-text text-muted small mb-0">
                                                By: <strong>{{ $activity->causer->name ?? 'System' }}</strong>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i>
                                                {{ $activity->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>

                                    @if($activity->changes)
                                        <hr>
                                        <div class="changes-detail">
                                            <small class="text-muted">
                                                @if(isset($activity->changes['attributes']))
                                                    <strong>Changes:</strong><br>
                                                    @foreach($activity->changes['attributes'] as $key => $value)
                                                        • {{ $key }}: {{ $value }}<br>
                                                    @endforeach
                                                @endif
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No activity recorded for this candidate yet.
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('candidates.profile', $candidate) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </div>
    </div>
</div>
@endsection