@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Breadcrumbs --}}
    <nav class="text-sm mb-4" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('dashboard.index') }}" class="text-blue-600 hover:text-blue-800">Dashboard</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-600">Audit Logs</span></li>
        </ol>
    </nav>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-history mr-2 text-blue-600"></i>Audit Logs
        </h2>
        <a href="{{ route('admin.activity-logs') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
            <i class="fas fa-list-alt mr-2"></i>Activity Logs
        </a>
    </div>

    {{-- Filters Card --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4"><i class="fas fa-filter mr-2"></i>Filters</h3>
        <form method="GET" action="{{ route('admin.audit-logs') }}" id="filter-form">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user_id" class="form-select w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                    <select name="event" class="form-select w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Events</option>
                        <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="form-input w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="form-input w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition" id="filter-btn">
                    <i class="fas fa-search mr-2"></i>Apply Filters
                </button>
                <a href="{{ route('admin.audit-logs') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Results Card --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-list mr-2"></i>Activity Records
            </h3>
            <span class="text-sm text-gray-500">
                Showing {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} records
            </span>
        </div>

        @if($logs->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock mr-2 text-gray-400"></i>
                                        {{ $log->created_at->format('M d, Y H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-blue-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $log->causer ? $log->causer->name : 'System' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $log->causer ? $log->causer->email : 'Automated' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $eventColors = [
                                            'created' => 'bg-green-100 text-green-800',
                                            'updated' => 'bg-blue-100 text-blue-800',
                                            'deleted' => 'bg-red-100 text-red-800',
                                        ];
                                        $color = $eventColors[$log->event] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                        {{ strtoupper($log->event ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                        {{ class_basename($log->subject_type ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    {{ $log->description ?? 'No description' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button type="button"
                                            class="text-blue-600 hover:text-blue-800 transition view-details-btn"
                                            data-log='@json($log->properties)'
                                            onclick="showLogDetails(this)">
                                        <i class="fas fa-eye mr-1"></i>Details
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $logs->withQueryString()->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-16">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-history text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Audit Logs Found</h3>
                <p class="text-gray-500 mb-4">There are no activity records matching your filters.</p>
                <a href="{{ route('admin.audit-logs') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-redo mr-1"></i>Clear filters and try again
                </a>
            </div>
        @endif
    </div>
</div>

{{-- Log Details Modal --}}
<div id="log-details-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeLogDetails()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-info-circle text-blue-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Log Details
                        </h3>
                        <div class="mt-4">
                            <pre id="log-details-content" class="bg-gray-50 p-4 rounded-lg text-sm overflow-auto max-h-80 text-left"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeLogDetails()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Show log details modal
    function showLogDetails(button) {
        const modal = document.getElementById('log-details-modal');
        const content = document.getElementById('log-details-content');
        const data = JSON.parse(button.dataset.log || '{}');
        content.textContent = JSON.stringify(data, null, 2);
        modal.classList.remove('hidden');
    }

    // Close log details modal
    function closeLogDetails() {
        const modal = document.getElementById('log-details-modal');
        modal.classList.add('hidden');
    }

    // Form loading state
    document.getElementById('filter-form').addEventListener('submit', function() {
        const btn = document.getElementById('filter-btn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Filtering...';
        btn.disabled = true;
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLogDetails();
        }
    });
</script>
@endpush
@endsection
