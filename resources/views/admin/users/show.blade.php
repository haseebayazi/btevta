@extends('layouts.app')
@section('title', 'User Details')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <h2>{{ $user->name }}</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Role:</strong> <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></p>
                    <p><strong>Campus:</strong> {{ $user->campus->name ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Phone:</strong> {{ $user->phone ?? '-' }}</p>
                    <p><strong>Status:</strong> 
                        @if($user->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </p>
                    <p><strong>Created:</strong> {{ $user->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection