@extends('layouts.app')
@section('title', 'Instructors')
@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Instructors</h2>
            <p class="text-gray-500 text-sm mt-1">Manage training instructors and their assignments</p>
        </div>
        <a href="{{ route('instructors.create') }}"
           class="mt-3 sm:mt-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add Instructor
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="{{ route('instructors.index') }}" class="flex flex-wrap gap-3 items-center">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, CNIC, or email..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="min-w-[160px]">
                <select name="campus_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <select name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="active"     {{ request('status') == 'active'     ? 'selected' : '' }}>Active</option>
                    <option value="inactive"   {{ request('status') == 'inactive'   ? 'selected' : '' }}>Inactive</option>
                    <option value="on_leave"   {{ request('status') == 'on_leave'   ? 'selected' : '' }}>On Leave</option>
                    <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                </select>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'campus_id', 'status']))
                <a href="{{ route('instructors.index') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <h5 class="font-semibold text-gray-800">
                <i class="fas fa-chalkboard-teacher mr-2 text-blue-500"></i>Instructor List
            </h5>
            <span class="text-sm text-gray-500">{{ $instructors->total() }} total</span>
        </div>

        @if($instructors->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Instructor</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Contact</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Campus</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Trade</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600">Employment</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-600">Status</th>
                            <th class="px-5 py-3 text-center font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($instructors as $instructor)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($instructor->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $instructor->name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $instructor->cnic }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-gray-800">{{ $instructor->email }}</p>
                                <p class="text-xs text-gray-500">{{ $instructor->phone }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $instructor->campus->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $instructor->trade->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @if($instructor->employment_type === 'permanent') bg-green-50 text-green-700
                                    @elseif($instructor->employment_type === 'contract') bg-amber-50 text-amber-700
                                    @else bg-gray-100 text-gray-600 @endif">
                                    {{ ucfirst($instructor->employment_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($instructor->status === 'active')     bg-green-100 text-green-800
                                    @elseif($instructor->status === 'inactive') bg-gray-100 text-gray-600
                                    @elseif($instructor->status === 'on_leave') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $instructor->status)) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('instructors.show', $instructor) }}"
                                       class="bg-cyan-50 text-cyan-600 hover:bg-cyan-100 p-1.5 rounded" title="View Profile">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('instructors.edit', $instructor) }}"
                                       class="bg-yellow-50 text-yellow-600 hover:bg-yellow-100 p-1.5 rounded" title="Edit">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('instructors.destroy', $instructor) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete {{ addslashes($instructor->name) }}? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-50 text-red-600 hover:bg-red-100 p-1.5 rounded" title="Delete">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t">
                {{ $instructors->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-chalkboard-teacher text-5xl mb-4"></i>
                <p class="text-lg font-medium text-gray-600">No instructors found</p>
                <p class="text-sm mt-1">
                    @if(request()->hasAny(['search', 'campus_id', 'status']))
                        Try adjusting your search or filters
                    @else
                        Get started by adding your first instructor
                    @endif
                </p>
                @if(!request()->hasAny(['search', 'campus_id', 'status']))
                    <a href="{{ route('instructors.create') }}"
                       class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Instructor
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
