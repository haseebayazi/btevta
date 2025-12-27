@extends('layouts.app')

@section('title', 'Equipment Details - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $equipment->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $equipment->equipment_code }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('equipment.edit', $equipment) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('equipment.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Equipment Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Campus</p>
                        <p class="font-medium">{{ $equipment->campus->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Category</p>
                        <p class="font-medium">{{ \App\Models\CampusEquipment::CATEGORIES[$equipment->category] ?? $equipment->category }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Brand</p>
                        <p class="font-medium">{{ $equipment->brand ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Model</p>
                        <p class="font-medium">{{ $equipment->model ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Serial Number</p>
                        <p class="font-medium">{{ $equipment->serial_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Quantity</p>
                        <p class="font-medium">{{ $equipment->quantity }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="font-medium">{{ $equipment->description ?? 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Financial Information</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Purchase Date</p>
                        <p class="font-medium">{{ $equipment->purchase_date?->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Purchase Cost</p>
                        <p class="font-medium">PKR {{ number_format($equipment->purchase_cost ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Current Value</p>
                        <p class="font-medium">PKR {{ number_format($equipment->current_value ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Usage History -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Recent Usage History</h3>
                @if($equipment->usageLogs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($equipment->usageLogs->take(10) as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">{{ $log->start_time->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ \App\Models\EquipmentUsageLog::USAGE_TYPES[$log->usage_type] ?? $log->usage_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $log->batch->batch_code ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($log->hours_used ?? 0, 1) }} hrs</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($log->status === 'completed')
                                        <span class="text-green-600"><i class="fas fa-check-circle"></i> Completed</span>
                                    @else
                                        <span class="text-yellow-600"><i class="fas fa-clock"></i> Active</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-gray-500 text-center py-8">No usage history recorded</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Status</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Current Status</p>
                        @php
                            $statusColors = [
                                'available' => 'bg-green-100 text-green-800',
                                'in_use' => 'bg-yellow-100 text-yellow-800',
                                'maintenance' => 'bg-orange-100 text-orange-800',
                                'retired' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$equipment->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ \App\Models\CampusEquipment::STATUSES[$equipment->status] ?? $equipment->status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Condition</p>
                        @php
                            $conditionColors = [
                                'excellent' => 'bg-green-100 text-green-800',
                                'good' => 'bg-blue-100 text-blue-800',
                                'fair' => 'bg-yellow-100 text-yellow-800',
                                'poor' => 'bg-orange-100 text-orange-800',
                                'unusable' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $conditionColors[$equipment->condition] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ \App\Models\CampusEquipment::CONDITIONS[$equipment->condition] ?? $equipment->condition }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Utilization Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Utilization</h3>
                @php $utilization = $equipment->getUtilizationRate(); @endphp
                <div class="text-center">
                    <div class="relative inline-flex items-center justify-center w-32 h-32">
                        <svg class="transform -rotate-90 w-32 h-32">
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200"></circle>
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" class="{{ $utilization >= 70 ? 'text-green-500' : ($utilization >= 40 ? 'text-yellow-500' : 'text-red-500') }}" stroke-dasharray="{{ 2 * 3.14159 * 56 }}" stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $utilization / 100) }}"></circle>
                        </svg>
                        <span class="absolute text-2xl font-bold">{{ $utilization }}%</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Monthly Utilization Rate</p>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500">Training Hours This Month</p>
                    <p class="text-xl font-bold text-gray-900">
                        {{ number_format($equipment->usageLogs()->forTraining()->thisMonth()->sum('hours_used'), 1) }} hrs
                    </p>
                </div>
            </div>

            <!-- Maintenance Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Maintenance</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Last Maintenance</p>
                        <p class="font-medium">{{ $equipment->last_maintenance_date?->format('M d, Y') ?? 'Never' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Next Maintenance</p>
                        @if($equipment->next_maintenance_date)
                            @php
                                $daysUntil = now()->diffInDays($equipment->next_maintenance_date, false);
                            @endphp
                            <p class="font-medium {{ $daysUntil <= 7 ? 'text-red-600' : ($daysUntil <= 14 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ $equipment->next_maintenance_date->format('M d, Y') }}
                                @if($daysUntil < 0)
                                    <span class="text-xs">(Overdue)</span>
                                @elseif($daysUntil <= 7)
                                    <span class="text-xs">({{ $daysUntil }} days)</span>
                                @endif
                            </p>
                        @else
                            <p class="font-medium text-gray-500">Not scheduled</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            @if($equipment->status === 'available')
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Quick Actions</h3>
                <button onclick="openLogUsageModal()" class="btn btn-primary w-full">
                    <i class="fas fa-play-circle mr-2"></i>Start Usage Session
                </button>
            </div>
            @endif
        </div>
    </div>

</div>

<!-- Log Usage Modal -->
<div id="logUsageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Log Equipment Usage</h3>
            <form action="{{ route('equipment.log-usage', $equipment) }}" method="POST">
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
function openLogUsageModal() {
    document.getElementById('logUsageModal').classList.remove('hidden');
}

function closeLogUsageModal() {
    document.getElementById('logUsageModal').classList.add('hidden');
}
</script>
@endpush
@endsection
