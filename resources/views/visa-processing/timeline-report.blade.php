@extends('layouts.app')
@section('title', 'Visa Timeline Report')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Visa Processing Timeline Report</h2>
    @if($data)
    <div class="card">
        <div class="card-body">
            <p><strong>Average Processing Days:</strong> {{ round($data->avg_days ?? 0) }} days</p>
        </div>
    </div>
    @endif
</div>
@endsection
