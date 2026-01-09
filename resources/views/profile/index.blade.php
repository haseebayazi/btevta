@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">My Profile</h1>
        <p class="text-gray-600 mt-1">Manage your account settings and password</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Profile Summary -->
        <div class="card text-center">
            <div class="w-24 h-24 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                <span class="text-3xl font-bold text-blue-600">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </span>
            </div>
            <h3 class="text-xl font-semibold">{{ $user->name }}</h3>
            <p class="text-gray-600">{{ $user->email }}</p>
            <div class="mt-4">
                <span class="badge badge-{{ $user->is_active ? 'success' : 'danger' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="badge badge-info ml-2">
                    {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                </span>
            </div>
            @if($user->campus)
                <p class="text-sm text-gray-500 mt-3">
                    <i class="fas fa-building mr-1"></i>{{ $user->campus->name }}
                </p>
            @endif
        </div>

        <!-- Profile Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-user mr-2 text-blue-500"></i>Personal Information
                </h2>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="form-label">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-input w-full @error('name') border-red-500 @enderror" required>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="form-label">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-input w-full @error('email') border-red-500 @enderror" required>
                            @error('email')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">Role</label>
                            <input type="text" value="{{ ucfirst(str_replace('_', ' ', $user->role)) }}"
                                   class="form-input w-full bg-gray-100" disabled>
                            <p class="text-sm text-gray-500 mt-1">Contact an administrator to change your role</p>
                        </div>

                        <div>
                            <label class="form-label">Campus</label>
                            <input type="text" value="{{ $user->campus->name ?? 'All Campuses' }}"
                                   class="form-input w-full bg-gray-100" disabled>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-lock mr-2 text-yellow-500"></i>Change Password
                </h2>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Include current name and email to satisfy validation -->
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">

                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="form-label">Current Password <span class="text-red-500">*</span></label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-input w-full @error('current_password') border-red-500 @enderror">
                            @error('current_password')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="new_password" class="form-label">New Password <span class="text-red-500">*</span></label>
                                <input type="password" id="new_password" name="new_password"
                                       class="form-input w-full @error('new_password') border-red-500 @enderror">
                                @error('new_password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-red-500">*</span></label>
                                <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                                       class="form-input w-full">
                            </div>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-medium text-blue-900 mb-2">Password Requirements:</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li><i class="fas fa-check-circle mr-1"></i>Minimum 8 characters</li>
                                <li><i class="fas fa-check-circle mr-1"></i>At least one uppercase letter</li>
                                <li><i class="fas fa-check-circle mr-1"></i>At least one lowercase letter</li>
                                <li><i class="fas fa-check-circle mr-1"></i>At least one number</li>
                                <li><i class="fas fa-check-circle mr-1"></i>At least one special character</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key mr-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Information -->
            <div class="card bg-gray-50">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2 text-gray-500"></i>Account Information
                </h2>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="text-gray-500">Account Created</label>
                        <p class="font-medium">{{ $user->created_at->format('M d, Y \a\t H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-gray-500">Last Updated</label>
                        <p class="font-medium">{{ $user->updated_at->format('M d, Y \a\t H:i') }}</p>
                    </div>
                    @if($user->password_changed_at)
                    <div>
                        <label class="text-gray-500">Password Last Changed</label>
                        <p class="font-medium">{{ $user->password_changed_at->format('M d, Y \a\t H:i') }}</p>
                    </div>
                    @endif
                    <div>
                        <label class="text-gray-500">Account Status</label>
                        <p class="font-medium">
                            @if($user->is_active)
                                <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Active</span>
                            @else
                                <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
