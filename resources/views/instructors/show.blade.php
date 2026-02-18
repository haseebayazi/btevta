@extends('layouts.app')
@section('title', 'Instructor – ' . $instructor->name)
@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('instructors.index') }}"
               class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $instructor->name }}</h2>
                <p class="text-gray-500 text-sm mt-0.5">Instructor Profile</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('instructors.edit', $instructor) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <form action="{{ route('instructors.destroy', $instructor) }}" method="POST" class="inline"
                  onsubmit="return confirm('Delete {{ addslashes($instructor->name) }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Sidebar --}}
        <div class="space-y-5">

            {{-- Avatar card --}}
            <div class="bg-white rounded-xl shadow-sm border p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-3xl font-bold mx-auto mb-4">
                    {{ strtoupper(substr($instructor->name, 0, 1)) }}
                </div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $instructor->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $instructor->specialization ?? $instructor->trade->name ?? 'Instructor' }}
                </p>
                <div class="mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                        @if($instructor->status === 'active')     bg-green-100 text-green-800
                        @elseif($instructor->status === 'inactive') bg-gray-100 text-gray-600
                        @elseif($instructor->status === 'on_leave') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-700 @endif">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5
                            @if($instructor->status === 'active')     bg-green-500
                            @elseif($instructor->status === 'inactive') bg-gray-400
                            @elseif($instructor->status === 'on_leave') bg-yellow-500
                            @else bg-red-500 @endif"></span>
                        {{ ucfirst(str_replace('_', ' ', $instructor->status)) }}
                    </span>
                </div>
                <div class="mt-4 pt-4 border-t flex justify-center gap-3">
                    <a href="mailto:{{ $instructor->email }}"
                       class="text-gray-400 hover:text-blue-600 transition-colors" title="Send Email">
                        <i class="fas fa-envelope text-lg"></i>
                    </a>
                    <a href="tel:{{ $instructor->phone }}"
                       class="text-gray-400 hover:text-green-600 transition-colors" title="Call">
                        <i class="fas fa-phone text-lg"></i>
                    </a>
                    <a href="{{ route('instructors.edit', $instructor) }}"
                       class="text-gray-400 hover:text-yellow-600 transition-colors" title="Edit">
                        <i class="fas fa-edit text-lg"></i>
                    </a>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="bg-white rounded-xl shadow-sm border divide-y">
                <div class="px-5 py-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Quick Stats</p>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Classes</span>
                    <span class="font-semibold text-gray-900">{{ $instructor->trainingClasses->count() }}</span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Active Classes</span>
                    <span class="font-semibold text-green-700">
                        {{ $instructor->trainingClasses->whereIn('status', ['scheduled', 'ongoing'])->count() }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Students</span>
                    <span class="font-semibold text-gray-900">
                        {{ $instructor->trainingClasses->sum('current_enrollment') }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Experience</span>
                    <span class="font-semibold text-gray-900">{{ $instructor->experience_years ?? 0 }} yrs</span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Tenure</span>
                    <span class="font-semibold text-gray-900">
                        @if($instructor->joining_date)
                            {{ $instructor->joining_date->diffInYears(now()) }} yrs
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>

            {{-- Employment --}}
            <div class="bg-white rounded-xl shadow-sm border divide-y">
                <div class="px-5 py-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employment</p>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Type</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        @if($instructor->employment_type === 'permanent') bg-green-50 text-green-700
                        @elseif($instructor->employment_type === 'contract') bg-amber-50 text-amber-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($instructor->employment_type) }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Joined</span>
                    <span class="text-sm font-medium text-gray-900">
                        {{ $instructor->joining_date?->format('M d, Y') ?? 'N/A' }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Campus</span>
                    <span class="text-sm font-medium text-gray-900">{{ $instructor->campus->name ?? '—' }}</span>
                </div>
                <div class="px-5 py-3 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Trade</span>
                    <span class="text-sm font-medium text-gray-900">{{ $instructor->trade->name ?? '—' }}</span>
                </div>
            </div>

            {{-- Record Info --}}
            <div class="bg-gray-50 rounded-xl border px-5 py-4 text-xs text-gray-400 space-y-1">
                <p>Created: {{ $instructor->created_at->format('M d, Y H:i') }}</p>
                <p>Updated: {{ $instructor->updated_at->format('M d, Y H:i') }}</p>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Personal Information --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-user mr-2 text-blue-500"></i>Personal Information
                    </h3>
                </div>
                <div class="p-6 grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Full Name</p>
                        <p class="text-gray-900 font-semibold">{{ $instructor->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">CNIC</p>
                        <p class="text-gray-900 font-mono">{{ $instructor->cnic }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email</p>
                        <a href="mailto:{{ $instructor->email }}"
                           class="text-blue-600 hover:underline">{{ $instructor->email }}</a>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone</p>
                        <a href="tel:{{ $instructor->phone }}"
                           class="text-blue-600 hover:underline">{{ $instructor->phone }}</a>
                    </div>
                    @if($instructor->address)
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Address</p>
                        <p class="text-gray-900">{{ $instructor->address }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Professional Information --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-briefcase mr-2 text-green-500"></i>Professional Information
                    </h3>
                </div>
                <div class="p-6 grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Qualification</p>
                        <p class="text-gray-900">{{ $instructor->qualification ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Specialization</p>
                        <p class="text-gray-900">{{ $instructor->specialization ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Years of Experience</p>
                        <p class="text-gray-900">{{ $instructor->experience_years ?? 0 }} years</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Employment Type</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                            @if($instructor->employment_type === 'permanent') bg-green-50 text-green-700
                            @elseif($instructor->employment_type === 'contract') bg-amber-50 text-amber-700
                            @else bg-gray-100 text-gray-600 @endif">
                            {{ ucfirst($instructor->employment_type) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Assigned Classes --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">
                        <i class="fas fa-chalkboard mr-2 text-purple-500"></i>Assigned Classes
                    </h3>
                    <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ $instructor->trainingClasses->count() }}
                    </span>
                </div>

                @if($instructor->trainingClasses && $instructor->trainingClasses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Class</th>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Trade</th>
                                    <th class="px-5 py-3 text-left font-medium text-gray-600">Enrollment</th>
                                    <th class="px-5 py-3 text-center font-medium text-gray-600">Status</th>
                                    <th class="px-5 py-3 text-center font-medium text-gray-600"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($instructor->trainingClasses as $class)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $class->class_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $class->class_code }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-gray-600">{{ $class->trade->name ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600">
                                                {{ $class->current_enrollment }}/{{ $class->max_capacity }}
                                            </span>
                                            <div class="w-20 bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-blue-500 h-1.5 rounded-full"
                                                     style="width: {{ min($class->capacity_percentage, 100) }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($class->status === 'ongoing')    bg-green-100 text-green-800
                                            @elseif($class->status === 'scheduled') bg-blue-100 text-blue-800
                                            @elseif($class->status === 'completed') bg-gray-100 text-gray-600
                                            @else bg-red-100 text-red-700 @endif">
                                            {{ ucfirst($class->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <a href="{{ route('classes.show', $class) }}"
                                           class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 p-1.5 rounded" title="View">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-chalkboard text-3xl mb-3"></i>
                        <p class="text-sm">No classes assigned yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
