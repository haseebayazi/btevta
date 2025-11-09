@extends('layouts.app')
@section('title', 'Edit Registration')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-info-circle"></i> Edit Registration Information</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-lightbulb"></i> <strong>How to Edit Registration:</strong>
                    </div>

                    <p>Registration details are edited through the candidate's registration detail page:</p>

                    <ol class="mb-4">
                        <li>Go to <strong>Registration Management</strong></li>
                        <li>Find the candidate you want to edit</li>
                        <li>Click "Manage" to view their registration details</li>
                        <li>Update documents, next of kin, or other registration information</li>
                    </ol>

                    <hr>

                    <div class="text-center">
                        <a href="{{ route('registration.index') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-list"></i> Go to Registration Management
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
