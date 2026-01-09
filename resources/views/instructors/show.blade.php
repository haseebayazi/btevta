@extends('layouts.app')

@section('title', 'Instructor Profile')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">{{ $instructor->name }}</h1>
            <p class="text-gray-600 mt-1">Instructor Profile</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('instructors.edit', $instructor) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('instructors.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Profile -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-user mr-2 text-blue-500"></i>Personal Information
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Full Name</label>
                        <p class="font-medium">{{ $instructor->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">CNIC</label>
                        <p class="font-medium font-mono">{{ $instructor->cnic }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Email</label>
                        <p class="font-medium">
                            <a href="mailto:{{ $instructor->email }}" class="text-blue-600 hover:underline">
                                {{ $instructor->email }}
                            </a>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Phone</label>
                        <p class="font-medium">
                            <a href="tel:{{ $instructor->phone }}" class="text-blue-600 hover:underline">
                                {{ $instructor->phone }}
                            </a>
                        </p>
                    </div>
                    @if($instructor->address)
                    <div class="md:col-span-2">
                        <label class="text-sm text-gray-500">Address</label>
                        <p class="font-medium">{{ $instructor->address }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Professional Information -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-briefcase mr-2 text-green-500"></i>Professional Information
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Campus</label>
                        <p class="font-medium">{{ $instructor->campus->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Trade</label>
                        <p class="font-medium">{{ $instructor->trade->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Qualification</label>
                        <p class="font-medium">{{ $instructor->qualification ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Specialization</label>
                        <p class="font-medium">{{ $instructor->specialization ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Experience</label>
                        <p class="font-medium">{{ $instructor->experience_years ?? 0 }} years</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Employment Type</label>
                        <p class="font-medium">{{ ucfirst($instructor->employment_type) }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Joining Date</label>
                        <p class="font-medium">{{ $instructor->joining_date?->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Status</label>
                        <p>
                            <span class="badge badge-{{ $instructor->status_badge_color }}">
                                {{ ucfirst(str_replace('_', ' ', $instructor->status)) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Assigned Classes -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">
                        <i class="fas fa-chalkboard mr-2 text-purple-500"></i>Assigned Classes
                    </h2>
                    <span class="badge badge-info">{{ $instructor->trainingClasses->count() }} Classes</span>
                </div>

                @if($instructor->trainingClasses && $instructor->trainingClasses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Trade</th>
                                    <th>Enrollment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($instructor->trainingClasses as $class)
                                <tr>
                                    <td>
                                        <div>
                                            <p class="font-medium">{{ $class->class_name }}</p>
                                            <p class="text-sm text-gray-500">{{ $class->class_code }}</p>
                                        </div>
                                    </td>
                                    <td>{{ $class->trade->name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span>{{ $class->current_enrollment }}/{{ $class->max_capacity }}</span>
                                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $class->capacity_percentage }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $class->status_badge_color }}">
                                            {{ ucfirst($class->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-chalkboard-teacher text-4xl mb-3"></i>
                        <p>No classes assigned yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Profile Card -->
            <div class="card text-center">
                <div class="w-24 h-24 bg-blue-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user-tie text-blue-600 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold">{{ $instructor->name }}</h3>
                <p class="text-gray-600">{{ $instructor->specialization ?? $instructor->trade->name ?? 'Instructor' }}</p>
                <div class="mt-4">
                    <span class="badge badge-{{ $instructor->status_badge_color }} text-sm px-4 py-1">
                        {{ ucfirst(str_replace('_', ' ', $instructor->status)) }}
                    </span>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-chart-bar mr-2 text-blue-500"></i>Quick Stats
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Classes</span>
                        <span class="font-medium">{{ $instructor->trainingClasses->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Active Classes</span>
                        <span class="font-medium">
                            {{ $instructor->trainingClasses->whereIn('status', ['scheduled', 'ongoing'])->count() }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Students</span>
                        <span class="font-medium">
                            {{ $instructor->trainingClasses->sum('current_enrollment') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tenure</span>
                        <span class="font-medium">
                            @if($instructor->joining_date)
                                {{ $instructor->joining_date->diffInYears(now()) }} years
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <h2 class="text-lg font-semibold mb-4">
                    <i class="fas fa-cogs mr-2 text-gray-500"></i>Actions
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('instructors.edit', $instructor) }}" class="btn btn-warning w-full justify-center">
                        <i class="fas fa-edit mr-2"></i>Edit Profile
                    </a>
                    <a href="mailto:{{ $instructor->email }}" class="btn btn-info w-full justify-center">
                        <i class="fas fa-envelope mr-2"></i>Send Email
                    </a>
                    <form action="{{ route('instructors.destroy', $instructor) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full justify-center"
                                onclick="return confirm('Are you sure you want to delete this instructor? This action cannot be undone.')">
                            <i class="fas fa-trash mr-2"></i>Delete Instructor
                        </button>
                    </form>
                </div>
            </div>

            <!-- Record Info -->
            <div class="card bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-600 mb-3">Record Information</h2>
                <div class="text-sm text-gray-500 space-y-1">
                    <p>Created: {{ $instructor->created_at->format('M d, Y H:i') }}</p>
                    <p>Updated: {{ $instructor->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
