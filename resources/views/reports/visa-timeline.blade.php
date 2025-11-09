@extends('layouts.app')
@section('title', 'Visa Timeline Report')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Visa Processing Timeline</h2>
    @if($visaData)
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Total Cases</h5>
                    <h2>{{ $visaData->total }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Avg Days</h5>
                    <h2>{{ round($visaData->avg_days ?? 0) }}</h2>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
