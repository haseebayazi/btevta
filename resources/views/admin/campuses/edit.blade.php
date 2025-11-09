@extends('layouts.app')
@section('title', 'Edit Campus')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Edit Campus: {{ $campus->name }}</h2>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('campuses.update', $campus->id) }}" method="POST">
                @csrf @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Campus Name *</label>
                            <input type="text" name="name" value="{{ $campus->name }}" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Location *</label>
                            <input type="text" name="location" value="{{ $campus->location }}" class="form-control @error('location') is-invalid @enderror" required>
                            @error('location') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Province *</label>
                            <input type="text" name="province" value="{{ $campus->province }}" class="form-control @error('province') is-invalid @enderror" required>
                            @error('province') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>District *</label>
                            <input type="text" name="district" value="{{ $campus->district }}" class="form-control @error('district') is-invalid @enderror" required>
                            @error('district') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contact Person *</label>
                            <input type="text" name="contact_person" value="{{ $campus->contact_person }}" class="form-control @error('contact_person') is-invalid @enderror" required>
                            @error('contact_person') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Phone *</label>
                            <input type="text" name="phone" value="{{ $campus->phone }}" class="form-control @error('phone') is-invalid @enderror" required>
                            @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="{{ $campus->email }}" class="form-control @error('email') is-invalid @enderror" required>
                            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3">{{ $campus->address }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <a href="{{ route('campuses.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Campus</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection