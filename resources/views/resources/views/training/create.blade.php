@extends('layouts.app')
@section('title', 'Add Candidates to Training')
@section('content')
<div class="container">
    <h2 class="mb-4">Add Candidates to Training</h2>
    <form method="POST" action="{{ route('training.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>Batch</label>
                <select name="batch_id" class="form-control" required>
                    <option value="">Select Batch</option>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}">{{ $batch->batch_number }}</option>
                    @endforeach
                </select>
                @error('batch_id')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Candidates</label>
                <select name="candidate_ids[]" class="form-control" multiple required size="10">
                    <option value="">Select Candidates...</option>
                </select>
                @error('candidate_ids')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Add to Training</button>
            <a href="{{ route('training.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
