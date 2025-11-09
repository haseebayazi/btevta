@extends('layouts.app')
@section('title', 'Screening Details')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-info-circle"></i> Screening Information</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i> <strong>How to View Screening Details:</strong>
                    </div>

                    <p>Screening details are viewable through the candidate's profile page:</p>

                    <ol class="mb-4">
                        <li>Go to <strong>Candidates</strong> module</li>
                        <li>Find the candidate whose screening you want to view</li>
                        <li>Click on their profile to see their complete screening history</li>
                    </ol>

                    <p>Or view all screening records from the Screening Management page.</p>

                    <hr>

                    <div class="text-center">
                        <a href="{{ route('screening.index') }}" class="btn btn-primary btn-lg mr-2">
                            <i class="fas fa-list"></i> View All Screenings
                        </a>
                        <a href="{{ route('screening.pending') }}" class="btn btn-warning btn-lg">
                            <i class="fas fa-clock"></i> Pending Screenings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
