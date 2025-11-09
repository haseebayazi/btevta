@extends('layouts.app')
@section('title', 'Create Trade')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Create New Trade</h2>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('trades.store') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label>Trade Name *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Trade Code *</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" required>
                    @error('category') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Duration (weeks) *</label>
                    <input type="number" name="duration_weeks" class="form-control @error('duration_weeks') is-invalid @enderror" min="1" required>
                    @error('duration_weeks') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <a href="{{ route('trades.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Trade</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection