@extends('layouts.app')
@section('title', 'New Registration')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-info-circle"></i> Registration Process Information</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i> <strong>How Registration Works:</strong>
                    </div>

                    <p>Registration is managed through the candidate management system. To register a new candidate:</p>

                    <ol class="mb-4">
                        <li>First, create or select a candidate from the <strong>Candidates</strong> module</li>
                        <li>The candidate should be in "Screening" or "Listed" status</li>
                        <li>Then access their registration details through the Registration module</li>
                        <li>Upload required documents, add next of kin information, and complete undertakings</li>
                        <li>Finally, complete the registration process</li>
                    </ol>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                                    <h5>New Candidate</h5>
                                    <p class="text-muted">Create a new candidate first</p>
                                    <a href="{{ route('candidates.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add Candidate
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-list fa-3x text-success mb-3"></i>
                                    <h5>Manage Registrations</h5>
                                    <p class="text-muted">View pending registrations</p>
                                    <a href="{{ route('registration.index') }}" class="btn btn-success">
                                        <i class="fas fa-list"></i> View Registrations
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
