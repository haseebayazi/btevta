@extends('layouts.app')
@section('title', 'Start Visa Process')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Start Visa Processing</h2>
            <p class="text-muted">Initiate visa processing for a candidate who has completed training</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('visa-processing.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-passport"></i> Visa Process Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('visa-processing.store') }}">
                        @csrf

                        <div class="form-group">
                            <label for="candidate_id">Candidate <span class="text-danger">*</span></label>
                            <select name="candidate_id" id="candidate_id" class="form-control @error('candidate_id') is-invalid @enderror" required>
                                <option value="">Select Candidate</option>
                                @if(isset($candidate))
                                    <option value="{{ $candidate->id }}" selected>
                                        {{ $candidate->name }} ({{ $candidate->btevta_id }}) - {{ $candidate->trade->name ?? 'N/A' }}
                                    </option>
                                @else
                                    @forelse($candidates ?? [] as $c)
                                        <option value="{{ $c->id }}" {{ old('candidate_id') == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} ({{ $c->btevta_id }}) - {{ $c->trade->name ?? 'N/A' }}
                                        </option>
                                    @empty
                                        <option value="" disabled>No candidates available for visa processing</option>
                                    @endforelse
                                @endif
                            </select>
                            @error('candidate_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                Select a candidate who has completed training and is ready for visa processing.
                            </small>
                        </div>

                        @if(isset($oeps) && $oeps->count() > 0)
                        <div class="form-group">
                            <label for="oep_id">Overseas Employment Promoter (OEP)</label>
                            <select name="oep_id" id="oep_id" class="form-control @error('oep_id') is-invalid @enderror">
                                <option value="">Select OEP (Optional)</option>
                                @foreach($oeps as $oep)
                                    <option value="{{ $oep->id }}" {{ old('oep_id') == $oep->id ? 'selected' : '' }}>
                                        {{ $oep->name }} - {{ $oep->country ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('oep_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        @endif

                        <div class="form-group">
                            <label for="notes">Initial Notes</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                      rows="3" placeholder="Any initial notes about this visa application...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>What happens next:</strong>
                            <ul class="mb-0 mt-2">
                                <li>The visa process will be initiated with "Interview Pending" status</li>
                                <li>You can track and update the progress through various stages</li>
                                <li>Documents and interview details can be added afterwards</li>
                            </ul>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('visa-processing.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-play"></i> Start Visa Process
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
