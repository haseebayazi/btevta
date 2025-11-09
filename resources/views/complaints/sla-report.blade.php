@extends('layouts.app')
@section('title', 'SLA Compliance Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">SLA Compliance Report</h1>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>

    <!-- Overall SLA Compliance -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Within SLA</p>
            <p class="text-3xl font-bold text-green-900">{{ $slaStats['within'] }}</p>
            <p class="text-sm text-green-700">{{ $slaStats['within_percentage'] }}%</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">SLA Breached</p>
            <p class="text-3xl font-bold text-red-900">{{ $slaStats['breached'] }}</p>
            <p class="text-sm text-red-700">{{ $slaStats['breached_percentage'] }}%</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">At Risk</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $slaStats['at_risk'] }}</p>
        </div>
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Avg Resolution Time</p>
            <p class="text-3xl font-bold text-blue-900">{{ number_format($slaStats['avg_time'], 1) }}h</p>
        </div>
    </div>

    <!-- SLA Performance by Category -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">SLA Performance by Category</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-center">SLA Target (hours)</th>
                        <th class="px-4 py-3 text-center">Within SLA</th>
                        <th class="px-4 py-3 text-center">Breached</th>
                        <th class="px-4 py-3 text-center">Compliance Rate</th>
                        <th class="px-4 py-3 text-center">Avg Resolution</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($categorySLA as $cat)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $cat->name }}</td>
                        <td class="px-4 py-3 text-center">{{ $cat->sla_target }}</td>
                        <td class="px-4 py-3 text-center text-green-600">{{ $cat->within_sla }}</td>
                        <td class="px-4 py-3 text-center text-red-600">{{ $cat->breached }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 rounded-full h-2" style="width: {{ $cat->compliance_rate }}%"></div>
                                </div>
                                <span class="badge badge-{{ $cat->compliance_rate >= 80 ? 'success' : 'danger' }}">
                                    {{ number_format($cat->compliance_rate, 1) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">{{ number_format($cat->avg_resolution, 1) }}h</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- SLA Breaches List -->
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Recent SLA Breaches</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Complaint ID</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-center">Filed Date</th>
                        <th class="px-4 py-3 text-center">SLA Target</th>
                        <th class="px-4 py-3 text-center">Actual Time</th>
                        <th class="px-4 py-3 text-center">Overdue By</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($breaches as $breach)
                    <tr class="bg-red-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('complaints.show', $breach) }}" class="text-blue-600 font-medium">
                                #{{ $breach->id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $breach->category }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $breach->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-center">{{ $breach->sla_target }}h</td>
                        <td class="px-4 py-3 text-center">{{ $breach->actual_time }}h</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-danger">{{ $breach->overdue_by }}h</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $breach->status_color }}">{{ ucfirst($breach->status) }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection