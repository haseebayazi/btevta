@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-4">Reports & Analytics</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Candidate Profile</h5>
                    <p class="card-text">Detailed candidate information</p>
                    <a href="#" class="btn btn-sm btn-primary" onclick="viewCandidateReport()">Generate</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Batch Summary</h5>
                    <p class="card-text">Training batch performance</p>
                    <a href="#" class="btn btn-sm btn-primary" onclick="viewBatchReport()">Generate</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Campus Performance</h5>
                    <p class="card-text">Campus-wise statistics</p>
                    <a href="{{ route('reports.campus-performance') }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">OEP Performance</h5>
                    <p class="card-text">OEP placement statistics</p>
                    <a href="{{ route('reports.oep-performance') }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Visa Timeline</h5>
                    <p class="card-text">Visa processing analytics</p>
                    <a href="{{ route('reports.visa-timeline') }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Training Statistics</h5>
                    <p class="card-text">Training performance data</p>
                    <a href="{{ route('reports.training-statistics') }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Complaint Analysis</h5>
                    <p class="card-text">Complaint statistics & trends</p>
                    <a href="{{ route('reports.complaint-analysis') }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Custom Report</h5>
                    <p class="card-text">Build custom reports</p>
                    <a href="{{ route('reports.custom-report') }}" class="btn btn-sm btn-primary">Build</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewCandidateReport() {
    alert('Select a candidate to generate report');
}

function viewBatchReport() {
    alert('Select a batch to generate report');
}
</script>
@endsection