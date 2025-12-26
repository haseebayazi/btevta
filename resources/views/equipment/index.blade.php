@extends('layouts.app')

@section('title', 'Equipment Inventory - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Equipment Inventory</h1>
            <p class="text-gray-600 mt-1">Manage campus equipment and track utilization</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('equipment.utilization-report') }}" class="btn btn-secondary">
                <i class="fas fa-chart-bar mr-2"></i>Utilization Report
            </a>
            <a href="{{ route('equipment.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Add Equipment
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('equipment.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="campus" class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" id="campus" class="form-select w-full">
                    <option value="">All Campuses</option>
                    @foreach($campuses ?? [] as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" id="category" class="form-select w-full">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\CampusEquipment::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="">All Status</option>
                    @foreach(\App\Models\CampusEquipment::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Equipment</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-boxes text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Available</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($stats['available'] ?? 0) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">In Use</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ number_format($stats['in_use'] ?? 0) }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-4">
                    <i class="fas fa-cog text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Needs Maintenance</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($stats['needs_maintenance'] ?? 0) }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-4">
                    <i class="fas fa-tools text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($equipment as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                                <p class="text-sm text-gray-500">{{ $item->equipment_code }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $item->campus->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ \App\Models\CampusEquipment::CATEGORIES[$item->category] ?? $item->category }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'available' => 'bg-green-100 text-green-800',
                                    'in_use' => 'bg-yellow-100 text-yellow-800',
                                    'maintenance' => 'bg-orange-100 text-orange-800',
                                    'retired' => 'bg-gray-100 text-gray-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\CampusEquipment::STATUSES[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $conditionColors = [
                                    'excellent' => 'text-green-600',
                                    'good' => 'text-blue-600',
                                    'fair' => 'text-yellow-600',
                                    'poor' => 'text-orange-600',
                                    'unusable' => 'text-red-600',
                                ];
                            @endphp
                            <span class="text-sm font-medium {{ $conditionColors[$item->condition] ?? 'text-gray-600' }}">
                                {{ \App\Models\CampusEquipment::CONDITIONS[$item->condition] ?? $item->condition }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php $utilization = $item->getUtilizationRate(); @endphp
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="{{ $utilization >= 70 ? 'bg-green-600' : ($utilization >= 40 ? 'bg-yellow-500' : 'bg-red-500') }} h-2 rounded-full" style="width: {{ min($utilization, 100) }}%"></div>
                                </div>
                                <span class="text-sm text-gray-600">{{ $utilization }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('equipment.show', $item) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('equipment.edit', $item) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($item->status === 'available')
                                <button onclick="openLogUsageModal({{ $item->id }})" class="text-green-600 hover:text-green-800" title="Log Usage">
                                    <i class="fas fa-play-circle"></i>
                                </button>
                                @endif
                                <form action="{{ route('equipment.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this equipment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-boxes text-4xl mb-3"></i>
                            <p>No equipment found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($equipment->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $equipment->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Log Usage Modal -->
<div id="logUsageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Log Equipment Usage</h3>
            <form id="logUsageForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usage Type</label>
                        <select name="usage_type" class="form-select w-full" required>
                            @foreach(\App\Models\EquipmentUsageLog::USAGE_TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch (Optional)</label>
                        <select name="batch_id" class="form-select w-full">
                            <option value="">Select Batch</option>
                            @foreach($batches ?? [] as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->batch_code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Number of Students</label>
                        <input type="number" name="students_count" class="form-input w-full" min="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" class="form-textarea w-full" rows="2"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeLogUsageModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Usage</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openLogUsageModal(equipmentId) {
    document.getElementById('logUsageForm').action = '/equipment/' + equipmentId + '/log-usage';
    document.getElementById('logUsageModal').classList.remove('hidden');
}

function closeLogUsageModal() {
    document.getElementById('logUsageModal').classList.add('hidden');
}
</script>
@endpush
@endsection
