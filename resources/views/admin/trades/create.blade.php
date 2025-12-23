@extends('layouts.app')
@section('title', 'Create Trade')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Create New Trade</h2>

    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.trades.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label>Trade Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Trade Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Category <span class="text-danger">*</span></label>
                    <input type="text" name="category" value="{{ old('category') }}" class="form-control @error('category') is-invalid @enderror" required>
                    @error('category') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Duration (weeks) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_weeks" value="{{ old('duration_weeks') }}" class="form-control @error('duration_weeks') is-invalid @enderror" min="1" required>
                    @error('duration_weeks') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description') }}</textarea>
                    @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <a href="{{ route('admin.trades.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Trade</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
