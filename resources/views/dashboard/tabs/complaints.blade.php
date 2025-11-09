@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Complaints Redressal Mechanism</h2>
        <a href="{{ route('complaints.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Register Complaint
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Total Complaints</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $complaintStats['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Pending</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $complaintStats['pending'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Resolved</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $complaintStats['resolved'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm font-bold text-red-600">OVERDUE SLA</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $complaintStats['overdue'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Complaints Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Complaint List</h3>
        
        <div class="overflow-x-auto">
            @if($complaintsList->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Complainant</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Category</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">SLA Days</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($complaintsList as $complaint)
                            @php
                                $daysRemaining = $complaint->registered_at->addDays($complaint->sla_days)->diffInDays(now());
                                $isOverdue = $daysRemaining < 0;
                            @endphp
                            <tr class="border-b hover:bg-gray-50 {{ $isOverdue ? 'bg-red-50' : '' }}">
                                <td class="px-6 py-4 font-mono">#{{ $complaint->id }}</td>
                                <td class="px-6 py-4">{{ $complaint->candidate->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                        {{ ucfirst(str_replace('_', ' ', $complaint->category)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                        @if($complaint->status === 'resolved') bg-green-100 text-green-800
                                        @elseif($complaint->status === 'in_progress') bg-blue-100 text-blue-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $complaint->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold {{ $isOverdue ? 'text-red-600 bg-red-100 px-2 py-1 rounded' : 'text-gray-700' }}">
                                        {{ abs($daysRemaining) }} {{ $isOverdue ? 'OVERDUE' : 'days' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('complaints.show', $complaint->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $complaintsList->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No complaints found</p>
            @endif
        </div>
    </div>
</div>
@endsection