@extends('layouts.app')
@section('title', 'Batch Summary Report')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4>Batch: {{ $batch->batch_number }} - Summary Report</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Total Candidates</h5>
                            <h2>{{ $totalCandidates }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Passed</h5>
                            <h2>{{ $passed }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Pass Rate</h5>
                            <h2>{{ $totalCandidates > 0 ? round(($passed/$totalCandidates)*100) : 0 }}%</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
