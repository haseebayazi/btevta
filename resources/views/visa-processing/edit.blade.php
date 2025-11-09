@extends('layouts.app')
@section('title', 'Edit Visa Process')
@section('content')
<div class="container">
    <h2 class="mb-4">Edit Visa Process</h2>
    <form method="POST" action="{{ route('visa-processing.update', $visaProcess) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            <p><strong>Candidate:</strong> {{ $visaProcess->candidate->name }}</p>
            <div class="form-group">
                <label>Interview Date</label>
                <input type="date" name="interview_date" class="form-control" value="{{ $visaProcess->interview_date }}">
            </div>
            <div class="form-group">
                <label>Interview Result</label>
                <select name="interview_result" class="form-control">
                    <option value="">Select</option>
                    <option value="pass" {{ $visaProcess->interview_result == 'pass' ? 'selected' : '' }}>Pass</option>
                    <option value="fail" {{ $visaProcess->interview_result == 'fail' ? 'selected' : '' }}>Fail</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('visa-processing.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
