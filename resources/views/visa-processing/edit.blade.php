@extends('layouts.app')
@section('title', 'Edit Visa Process')
@section('content')
<div class="container">
    <h2 class="mb-4">Edit Visa Process</h2>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('visa-processing.update', $visaProcess) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            <p><strong>Candidate:</strong> {{ $visaProcess->candidate->name }}</p>
            <div class="form-group">
                <label>Interview Date</label>
                <input type="date" name="interview_date" class="form-control @error('interview_date') is-invalid @enderror"
                       value="{{ old('interview_date', $visaProcess->interview_date ? \Carbon\Carbon::parse($visaProcess->interview_date)->format('Y-m-d') : '') }}">
                @error('interview_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label>Interview Result</label>
                <select name="interview_result" class="form-control @error('interview_result') is-invalid @enderror">
                    <option value="">Select</option>
                    <option value="pass" {{ old('interview_result', $visaProcess->interview_result) == 'pass' ? 'selected' : '' }}>Pass</option>
                    <option value="fail" {{ old('interview_result', $visaProcess->interview_result) == 'fail' ? 'selected' : '' }}>Fail</option>
                </select>
                @error('interview_result')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('visa-processing.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
