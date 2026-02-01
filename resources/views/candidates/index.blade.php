{{-- ============================================ --}}
{{-- File: resources/views/candidates/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Candidates Listing')

@section('content')
<div class="space-y-6" x-data="bulkOperations()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-2xl font-bold text-gray-900">Candidates Listing</h2>
        <div class="flex flex-wrap gap-2 sm:gap-3">
            <a href="{{ route('import.candidates.form') }}" class="bg-green-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-green-700 text-sm sm:text-base">
                <i class="fas fa-file-import mr-1 sm:mr-2"></i><span class="hidden sm:inline">Import from Excel</span><span class="sm:hidden">Import</span>
            </a>
            <a href="{{ route('candidates.create') }}" class="bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 text-sm sm:text-base">
                <i class="fas fa-plus mr-1 sm:mr-2"></i><span class="hidden sm:inline">Add Candidate</span><span class="sm:hidden">Add</span>
            </a>
        </div>
    </div>

    <!-- Bulk Actions Bar (appears when candidates selected) -->
    <div x-show="selectedIds.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="bg-blue-50 border border-blue-200 rounded-lg p-4 sticky top-16 z-30">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center space-x-3">
                <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold" x-text="selectedIds.length + ' selected'"></span>
                <button @click="clearSelection()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>

            <div class="flex flex-wrap gap-2">
                <!-- Status Update Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 text-sm flex items-center">
                        <i class="fas fa-exchange-alt mr-2"></i>Change Status
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-50">
                        <div class="py-1">
                            @foreach(['screening' => 'Screening', 'registered' => 'Registered', 'training' => 'Training', 'visa_process' => 'Visa Process', 'ready' => 'Ready', 'departed' => 'Departed'] as $status => $label)
                            <button @click="bulkUpdateStatus('{{ $status }}'); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ $label }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Batch Assignment -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 text-sm flex items-center">
                        <i class="fas fa-layer-group mr-2"></i>Assign Batch
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border z-50 max-h-64 overflow-y-auto">
                        <div class="py-1">
                            @foreach($batches as $batch)
                            <button @click="bulkAssignBatch({{ $batch->id }}); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ $batch->batch_code ?? $batch->name }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Export -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 text-sm flex items-center">
                        <i class="fas fa-download mr-2"></i>Export
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border z-50">
                        <div class="py-1">
                            <button @click="bulkExport('csv'); open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-csv mr-2 text-green-600"></i>CSV
                            </button>
                            <button @click="bulkExport('excel'); open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-excel mr-2 text-green-600"></i>Excel
                            </button>
                            <button @click="bulkExport('pdf'); open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-pdf mr-2 text-red-600"></i>PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Delete (Admin only) -->
                @if(auth()->user()->isAdmin())
                <button @click="bulkDelete()" class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 text-sm">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <input type="text" name="search" placeholder="Search by Name, CNIC, TheLeap ID"
                   value="{{ request('search') }}" class="px-4 py-2 border rounded-lg w-full">

            <select name="status" class="px-4 py-2 border rounded-lg w-full">
                <option value="">All Status</option>
                @foreach(\App\Enums\CandidateStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>

            <select name="trade_id" class="px-4 py-2 border rounded-lg w-full">
                <option value="">All Trades</option>
                @foreach($trades as $trade)
                    <option value="{{ $trade->id }}" {{ request('trade_id') == $trade->id ? 'selected' : '' }}>{{ $trade->name }}</option>
                @endforeach
            </select>

            <select name="batch_id" class="px-4 py-2 border rounded-lg w-full">
                <option value="">All Batches</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->batch_code ?? $batch->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full sm:w-auto">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </form>
    </div>

    <!-- Candidates Table -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            @if($candidates->count() > 0)
                <table class="w-full text-sm min-w-[800px]">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 sm:px-6 py-3 text-left">
                                <input type="checkbox" @change="toggleAll($event)"
                                       :checked="selectedIds.length === {{ $candidates->count() }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700 hidden lg:table-cell">TheLeap ID</th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700 hidden md:table-cell">CNIC</th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700 hidden xl:table-cell">Campus</th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-3 sm:px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($candidates as $candidate)
                            <tr class="border-b hover:bg-gray-50" :class="{ 'bg-blue-50': selectedIds.includes({{ $candidate->id }}) }">
                                <td class="px-3 sm:px-6 py-4">
                                    <input type="checkbox" value="{{ $candidate->id }}"
                                           @change="toggleCandidate({{ $candidate->id }})"
                                           :checked="selectedIds.includes({{ $candidate->id }})"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-3 sm:px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $candidate->name }}</div>
                                    <div class="text-xs text-gray-500 lg:hidden">{{ $candidate->btevta_id }}</div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 font-mono hidden lg:table-cell">{{ $candidate->btevta_id }}</td>
                                <td class="px-3 sm:px-6 py-4 font-mono hidden md:table-cell">{{ $candidate->cnic ?? '-' }}</td>
                                <td class="px-3 sm:px-6 py-4 hidden xl:table-cell">{{ $candidate->campus->name ?? 'N/A' }}</td>
                                <td class="px-3 sm:px-6 py-4">
                                    <span class="truncate max-w-[100px] block" title="{{ $candidate->trade->name ?? 'N/A' }}">
                                        {{ $candidate->trade->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-6 py-4">
                                    @php
                                        $statusEnum = \App\Enums\CandidateStatus::tryFrom($candidate->status);
                                        $statusColor = $statusEnum ? $statusEnum->color() : 'gray';
                                        $statusLabel = $statusEnum ? $statusEnum->label() : ucfirst(str_replace('_', ' ', $candidate->status));
                                    @endphp
                                    <span class="inline-block px-2 sm:px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('candidates.show', $candidate->id) }}"
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                           <i class="fas fa-eye sm:hidden"></i>
                                           <span class="hidden sm:inline">View</span>
                                        </a>
                                        <a href="{{ route('candidates.edit', $candidate->id) }}"
                                           class="text-green-600 hover:text-green-900 text-sm font-medium">
                                           <i class="fas fa-edit sm:hidden"></i>
                                           <span class="hidden sm:inline">Edit</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="mt-4 px-4 sm:px-0">
                    {{ $candidates->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-users text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No candidates found</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Bulk Operation Loading Modal -->
    <div x-show="loading" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 text-center">
            <i class="fas fa-spinner fa-spin text-blue-600 text-4xl mb-4"></i>
            <p class="text-gray-700 font-medium" x-text="loadingMessage"></p>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
         class="fixed bottom-4 right-4 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-3">
        <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
        <span x-text="toast.message"></span>
    </div>
</div>

@push('scripts')
<script>
function bulkOperations() {
    return {
        selectedIds: [],
        loading: false,
        loadingMessage: 'Processing...',
        toast: { show: false, message: '', type: 'success' },
        allCandidateIds: @json($candidates->pluck('id')->toArray()),

        toggleCandidate(id) {
            const index = this.selectedIds.indexOf(id);
            if (index > -1) {
                this.selectedIds.splice(index, 1);
            } else {
                this.selectedIds.push(id);
            }
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...this.allCandidateIds];
            } else {
                this.selectedIds = [];
            }
        },

        clearSelection() {
            this.selectedIds = [];
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        async bulkUpdateStatus(status) {
            if (!confirm(`Change status of ${this.selectedIds.length} candidates to "${status}"?`)) return;

            this.loading = true;
            this.loadingMessage = 'Updating status...';

            try {
                const response = await axios.post('/bulk/candidates/status', {
                    candidate_ids: this.selectedIds,
                    status: status
                });

                if (response.data.success) {
                    this.showToast(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showToast(response.data.message, 'error');
                }
            } catch (error) {
                this.showToast(error.response?.data?.message || 'Operation failed', 'error');
            } finally {
                this.loading = false;
            }
        },

        async bulkAssignBatch(batchId) {
            if (!confirm(`Assign ${this.selectedIds.length} candidates to this batch?`)) return;

            this.loading = true;
            this.loadingMessage = 'Assigning to batch...';

            try {
                const response = await axios.post('/bulk/candidates/batch', {
                    candidate_ids: this.selectedIds,
                    batch_id: batchId
                });

                if (response.data.success) {
                    this.showToast(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                this.showToast(error.response?.data?.message || 'Operation failed', 'error');
            } finally {
                this.loading = false;
            }
        },

        async bulkExport(format) {
            this.loading = true;
            this.loadingMessage = 'Preparing export...';

            try {
                const response = await axios.post('/bulk/candidates/export', {
                    candidate_ids: this.selectedIds,
                    format: format
                });

                if (response.data.success && response.data.download_url) {
                    window.location.href = response.data.download_url;
                    this.showToast(response.data.message, 'success');
                }
            } catch (error) {
                this.showToast(error.response?.data?.message || 'Export failed', 'error');
            } finally {
                this.loading = false;
            }
        },

        async bulkDelete() {
            if (!confirm(`Are you sure you want to DELETE ${this.selectedIds.length} candidates? This action cannot be undone.`)) return;
            if (!confirm('Please confirm again - this will permanently remove these records.')) return;

            this.loading = true;
            this.loadingMessage = 'Deleting candidates...';

            try {
                const response = await axios.post('/bulk/candidates/delete', {
                    candidate_ids: this.selectedIds
                });

                if (response.data.success) {
                    this.showToast(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                this.showToast(error.response?.data?.message || 'Delete failed', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
