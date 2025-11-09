@extends('layouts.app')
@section('title', 'Training Classes')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Training Classes</h1>
        <a href="{{ route('classes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Add New Class
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" action="{{ route('classes.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by class name or code..."
                       class="form-input w-full">
            </div>
            <div class="min-w-[150px]">
                <select name="campus_id" class="form-select w-full">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <select name="status" class="form-select w-full">
                    <option value="">All Status</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <!-- Classes List -->
    <div class="card">
        @if($classes->count() > 0)
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Instructor</th>
                            <th>Start Date</th>
                            <th>Enrollment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $class)
                        <tr>
                            <td class="font-mono">{{ $class->class_code }}</td>
                            <td>{{ $class->class_name }}</td>
                            <td>{{ $class->campus->name ?? 'N/A' }}</td>
                            <td>{{ $class->trade->name ?? 'N/A' }}</td>
                            <td>{{ $class->instructor->name ?? 'N/A' }}</td>
                            <td>{{ $class->start_date ? $class->start_date->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span>{{ $class->current_enrollment }}/{{ $class->max_capacity }}</span>
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
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
                                <div class="flex gap-2">
                                    <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('classes.destroy', $class) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this class?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $classes->links() }}
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-chalkboard text-4xl mb-3"></i>
                <p>No training classes found.</p>
            </div>
        @endif
    </div>
</div>
@endsection
