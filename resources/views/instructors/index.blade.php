@extends('layouts.app')
@section('title', 'Instructors')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Instructors</h1>
        <a href="{{ route('instructors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Add New Instructor
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" action="{{ route('instructors.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, CNIC, or email..."
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
                    @php
                        $instructorStatuses = config('statuses.instructor', [
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'on_leave' => 'On Leave',
                            'terminated' => 'Terminated',
                        ]);
                    @endphp
                    @foreach($instructorStatuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('instructors.index') }}" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <!-- Instructors List -->
    <div class="card">
        @if($instructors->count() > 0)
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Campus</th>
                            <th>Trade</th>
                            <th>Employment Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($instructors as $instructor)
                        <tr>
                            <td>{{ $instructor->id }}</td>
                            <td>{{ $instructor->name }}</td>
                            <td>{{ $instructor->cnic }}</td>
                            <td>{{ $instructor->email }}</td>
                            <td>{{ $instructor->phone }}</td>
                            <td>{{ $instructor->campus->name ?? 'N/A' }}</td>
                            <td>{{ $instructor->trade->name ?? 'N/A' }}</td>
                            <td>{{ ucfirst($instructor->employment_type) }}</td>
                            <td>
                                <span class="badge badge-{{ $instructor->status_badge_color }}">
                                    {{ ucfirst(str_replace('_', ' ', $instructor->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('instructors.show', $instructor) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('instructors.edit', $instructor) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('instructors.destroy', $instructor) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this instructor?')">
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
                {{ $instructors->links() }}
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-chalkboard-teacher text-4xl mb-3"></i>
                <p>No instructors found.</p>
            </div>
        @endif
    </div>
</div>
@endsection
