@extends('layouts.admin')

@section('title', 'Employers')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Employer Information</h1>
            <p class="text-gray-600 mt-1">Manage employer details and employment packages</p>
        </div>

        @can('create', App\Models\Employer::class)
            <a href="{{ route('admin.employers.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Employer
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" action="{{ route('admin.employers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by company name..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <select name="country_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                                {{ request('country_id') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="is_active"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex-1">
                    Filter
                </button>
                <a href="{{ route('admin.employers.index') }}"
                   class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Employers Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Company Information
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Country
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Employment Package
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employers as $employer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $employer->visa_issuing_company }}
                            </div>
                            @if($employer->permission_number)
                                <div class="text-sm text-gray-500">
                                    Permission #: {{ $employer->permission_number }}
                                </div>
                            @endif
                            @if($employer->sector || $employer->trade)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $employer->sector }} {{ $employer->trade ? '- ' . $employer->trade : '' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($employer->country->flag_emoji)
                                    <span class="text-xl">{{ $employer->country->flag_emoji }}</span>
                                @endif
                                <span class="text-sm text-gray-900">{{ $employer->country->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($employer->basic_salary)
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $employer->salary_currency ?? 'PKR' }} {{ number_format($employer->basic_salary) }}
                                </div>
                            @endif
                            <div class="flex gap-1 mt-1">
                                @if($employer->food_by_company)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Food</span>
                                @endif
                                @if($employer->accommodation_by_company)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Accommodation</span>
                                @endif
                                @if($employer->transport_by_company)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Transport</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                       {{ $employer->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $employer->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.employers.show', $employer) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    View
                                </a>
                                @can('update', $employer)
                                    <a href="{{ route('admin.employers.edit', $employer) }}"
                                       class="text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>
                                @endcan
                                @can('delete', $employer)
                                    <form action="{{ route('admin.employers.destroy', $employer) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this employer?');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No employers found. <a href="{{ route('admin.employers.create') }}" class="text-blue-600 hover:underline">Add the first employer</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($employers->hasPages())
        <div class="mt-6">
            {{ $employers->links() }}
        </div>
    @endif

    <!-- Statistics Panel -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-600">Total Employers</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employers->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-600">Active</div>
            <div class="text-2xl font-bold text-green-600">
                {{ $employers->where('is_active', true)->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-600">By Countries</div>
            <div class="text-2xl font-bold text-blue-600">
                {{ $employers->unique('country_id')->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-600">With Benefits</div>
            <div class="text-2xl font-bold text-purple-600">
                {{ $employers->where(function($e) {
                    return $e->food_by_company || $e->accommodation_by_company || $e->transport_by_company;
                })->count() }}
            </div>
        </div>
    </div>
</div>
@endsection
