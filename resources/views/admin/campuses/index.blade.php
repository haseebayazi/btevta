@extends('layouts.app')
@section('title', 'Campus Management')
@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Campus Management</h2>
            <p class="text-gray-600 mt-1">Manage BTEVTA campus locations and details</p>
        </div>
        <div>
            @can('create', App\Models\Campus::class)
            <a href="{{ route('admin.campuses.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>Add New Campus
            </a>
            @endcan
        </div>
    </div>

    @if($campuses->count())
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statistics</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($campuses as $campus)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-building text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ $campus->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $campus->province }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $campus->location }}</div>
                                    <div class="text-xs text-gray-500">{{ $campus->district }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $campus->contact_person }}</div>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-phone mr-1"></i>{{ $campus->phone }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fas fa-users mr-1"></i>{{ $campus->candidates_count ?? 0 }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                            <i class="fas fa-layer-group mr-1"></i>{{ $campus->batches_count ?? 0 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($campus->is_active ?? true)
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    @can('view', $campus)
                                    <a href="{{ route('admin.campuses.show', $campus->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan
                                    @can('update', $campus)
                                    <a href="{{ route('admin.campuses.edit', $campus->id) }}" class="inline-flex items-center px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete', $campus)
                                    <form action="{{ route('admin.campuses.destroy', $campus->id) }}" method="POST" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700" onclick="return confirm('Delete this campus and all associated data?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">
            {{ $campuses->links() }}
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>No campuses found. Click "Add New Campus" to get started.
            </p>
        </div>
    @endif
</div>
@endsection
