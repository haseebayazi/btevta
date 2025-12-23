@extends('layouts.app')
@section('title', 'Edit Training')
@section('content')
<div class="container">
    <h2 class="mb-4">Edit Training</h2>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('training.update', $candidate) }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label>Candidate: <strong>{{ $candidate->name }}</strong></label>
            </div>
            <div class="form-group">
                <label>Batch <span class="text-danger">*</span></label>
                <select name="batch_id" class="form-control @error('batch_id') is-invalid @enderror" required>
                    <option value="">Select Batch</option>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ old('batch_id', $candidate->batch_id) == $batch->id ? 'selected' : '' }}>
                        {{ $batch->batch_number }}
                    </option>
                    @endforeach
                </select>
                @error('batch_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('training.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
