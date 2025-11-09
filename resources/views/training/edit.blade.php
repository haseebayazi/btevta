@extends('layouts.app')
@section('title', 'Edit Training')
@section('content')
<div class="container">
    <h2 class="mb-4">Edit Training</h2>
    <form method="POST" action="{{ route('training.update', $candidate) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label>Candidate: {{ $candidate->name }}</label>
            </div>
            <div class="form-group">
                <label>Batch</label>
                <select name="batch_id" class="form-control" required>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ $candidate->batch_id == $batch->id ? 'selected' : '' }}>
                        {{ $batch->batch_number }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('training.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
