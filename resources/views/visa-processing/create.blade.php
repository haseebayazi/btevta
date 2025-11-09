@extends('layouts.app')
@section('title', 'Start Visa Process')
@section('content')
<div class="container">
    <h2 class="mb-4">Start Visa Processing</h2>
    <form method="POST" action="{{ route('visa-processing.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>Candidate</label>
                <select name="candidate_id" class="form-control" required>
                    <option value="">Select Candidate</option>
                    @if(isset($candidate))
                        <option value="{{ $candidate->id }}" selected>{{ $candidate->name }}</option>
                    @else
                        @foreach($candidates ?? [] as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->btevta_id }})</option>
                        @endforeach
                    @endif
                </select>
                @error('candidate_id')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Start Process</button>
            <a href="{{ route('visa-processing.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
