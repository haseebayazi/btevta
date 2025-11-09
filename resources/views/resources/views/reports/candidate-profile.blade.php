@extends('layouts.app')
@section('title', 'Candidate Profile Report')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ $candidate->name }} - Profile Report</h4>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">BTEVTA ID:</dt>
                        <dd class="col-sm-9">{{ $candidate->btevta_id }}</dd>
                        <dt class="col-sm-3">CNIC:</dt>
                        <dd class="col-sm-9">{{ $candidate->cnic }}</dd>
                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9"><span class="badge badge-primary">{{ $candidate->status_label }}</span></dd>
                        <dt class="col-sm-3">Trade:</dt>
                        <dd class="col-sm-9">{{ $candidate->trade?->name ?? 'N/A' }}</dd>
                        <dt class="col-sm-3">Campus:</dt>
                        <dd class="col-sm-9">{{ $candidate->campus?->name ?? 'N/A' }}</dd>
                    </dl>
                    <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-primary">View Full Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
