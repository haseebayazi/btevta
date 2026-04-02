@extends('layouts.app')
@section('title', 'Post-Departure Details - ' . $candidate->name)
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><i class="fas fa-globe mr-2 text-blue-600"></i>Post-Departure Details</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $candidate->name }} &mdash; {{ $candidate->btevta_id ?? $candidate->cnic }}</p>
        </div>
        <a href="{{ route('post-departure.dashboard') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    @include('post-departure.partials.compliance-checklist', ['checklist' => $checklist, 'detail' => $detail])
    @include('post-departure.partials.iqama-card', ['detail' => $detail])
    @include('post-departure.partials.contact-card', ['detail' => $detail])
    @include('post-departure.partials.bank-card', ['detail' => $detail])
    @include('post-departure.partials.employment-card', ['detail' => $detail, 'employmentHistory' => $employmentHistory])
    @include('post-departure.partials.switch-card', ['detail' => $detail, 'switches' => $switches])
</div>
@endsection
