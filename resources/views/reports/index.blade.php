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
                    <div class="input-group">
                        <select id="candidate-select" class="form-select form-select-sm">
                            <option value="">Select Candidate...</option>
                            @foreach(\App\Models\Candidate::select('id', 'name', 'btevta_id')->orderBy('name')->limit(100)->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->btevta_id }})</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="viewCandidateReport()">Generate</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Batch Summary</h5>
                    <p class="card-text">Training batch performance</p>
                    <div class="input-group">
                        <select id="batch-select" class="form-select form-select-sm">
                            <option value="">Select Batch...</option>
                            @foreach(\App\Models\Batch::select('id', 'name')->orderBy('created_at', 'desc')->limit(50)->get() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="viewBatchReport()">Generate</button>
                    </div>
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
    const candidateId = document.getElementById('candidate-select').value;
    if (!candidateId) {
        alert('Please select a candidate first');
        return;
    }
    window.location.href = '{{ url("candidates") }}/' + candidateId;
}

function viewBatchReport() {
    const batchId = document.getElementById('batch-select').value;
    if (!batchId) {
        alert('Please select a batch first');
        return;
    }
    window.location.href = '{{ url("training/batch") }}/' + batchId + '/performance';
}
</script>
@endsection