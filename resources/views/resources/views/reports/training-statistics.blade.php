@extends('layouts.app')
@section('title', 'Training Statistics')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Training Statistics</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5>In Training</h5>
                    <h2>{{ $totalInTraining }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5>Completed</h5>
                    <h2>{{ $totalCompleted }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
