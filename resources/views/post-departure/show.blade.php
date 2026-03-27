@extends('layouts.app')
@section('title', 'Post-Departure Details - ' . $candidate->name)
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-globe"></i> Post-Departure Details</h2>
            <p class="text-muted">{{ $candidate->name }} ({{ $candidate->btevta_id ?? $candidate->cnic }})</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('post-departure.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    <!-- Compliance Checklist -->
    @include('post-departure.partials.compliance-checklist', ['checklist' => $checklist, 'detail' => $detail])

    <!-- Iqama / Residency -->
    @include('post-departure.partials.iqama-card', ['detail' => $detail])

    <!-- Foreign Contact -->
    @include('post-departure.partials.contact-card', ['detail' => $detail])

    <!-- Foreign Bank -->
    @include('post-departure.partials.bank-card', ['detail' => $detail])

    <!-- Employment Details -->
    @include('post-departure.partials.employment-card', ['detail' => $detail, 'employmentHistory' => $employmentHistory])

    <!-- Company Switch -->
    @include('post-departure.partials.switch-card', ['detail' => $detail, 'switches' => $switches])
</div>
@endsection
