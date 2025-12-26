@extends('layouts.app')
@section('title', 'Correspondence Summary')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Communication Summary Report</h1>
        <a href="{{ route('correspondence.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Correspondence
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-6 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total</p>
            <p class="text-3xl font-bold text-blue-900">{{ $summary['total'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Incoming</p>
            <p class="text-3xl font-bold text-green-900">{{ $summary['incoming'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Outgoing</p>
            <p class="text-3xl font-bold text-purple-900">{{ $summary['outgoing'] }}</p>
        </div>
        <div class="card bg-indigo-50">
            <p class="text-sm text-indigo-800">In/Out Ratio</p>
            <p class="text-3xl font-bold text-indigo-900">{{ $summary['ratio'] }}</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">Pending Replies</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $summary['pending_replies'] }}</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Overdue Replies</p>
            <p class="text-3xl font-bold text-red-900">{{ $summary['overdue_replies'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-5 gap-4">
            <div>
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" value="{{ $validated['start_date'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" value="{{ $validated['end_date'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Organization Type</label>
                <select name="organization_type" class="form-input">
                    <option value="">All Organizations</option>
                    @foreach($organizationTypes as $key => $label)
                        <option value="{{ $key }}" {{ ($validated['organization_type'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Campus</label>
                <select name="campus_id" class="form-input">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ ($validated['campus_id'] ?? '') == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Filter</button>
            </div>
        </form>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- By Organization Type -->
        <div class="card">
            <h3 class="text-lg font-bold mb-4">By Organization Type</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Organization</th>
                            <th class="px-4 py-3 text-center">Incoming</th>
                            <th class="px-4 py-3 text-center">Outgoing</th>
                            <th class="px-4 py-3 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($summary['by_organization'] as $orgType => $items)
                        @php
                            $incoming = $items->firstWhere('type', 'incoming')->count ?? 0;
                            $outgoing = $items->firstWhere('type', 'outgoing')->count ?? 0;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium capitalize">{{ $orgType ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-center text-green-600">{{ $incoming }}</td>
                            <td class="px-4 py-3 text-center text-purple-600">{{ $outgoing }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $incoming + $outgoing }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No data available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Response Time -->
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Response Metrics</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">Average Response Time</span>
                    <span class="text-2xl font-bold text-blue-600">{{ $summary['avg_response_time'] }} days</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">Pending Replies</span>
                    <span class="text-2xl font-bold text-yellow-600">{{ $summary['pending_replies'] }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">Overdue Replies</span>
                    <span class="text-2xl font-bold text-red-600">{{ $summary['overdue_replies'] }}</span>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">Communication Ratio (In:Out)</span>
                    <span class="text-2xl font-bold text-indigo-600">{{ $summary['ratio'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card mt-6">
        <h3 class="text-lg font-bold mb-4">Monthly Trend (Last 12 Months)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Month</th>
                        <th class="px-4 py-3 text-center">Incoming</th>
                        <th class="px-4 py-3 text-center">Outgoing</th>
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-left">Trend</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($summary['by_month'] as $month => $items)
                    @php
                        $incoming = $items->firstWhere('type', 'incoming')->count ?? 0;
                        $outgoing = $items->firstWhere('type', 'outgoing')->count ?? 0;
                        $total = $incoming + $outgoing;
                        $maxTotal = collect($summary['by_month'])->map(function($m) {
                            return ($m->firstWhere('type', 'incoming')->count ?? 0) + ($m->firstWhere('type', 'outgoing')->count ?? 0);
                        })->max() ?: 1;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ \Carbon\Carbon::parse($month . '-01')->format('M Y') }}</td>
                        <td class="px-4 py-3 text-center text-green-600">{{ $incoming }}</td>
                        <td class="px-4 py-3 text-center text-purple-600">{{ $outgoing }}</td>
                        <td class="px-4 py-3 text-center font-bold">{{ $total }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-xs">
                                    <div class="bg-blue-600 rounded-full h-2" style="width: {{ ($total / $maxTotal) * 100 }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No monthly data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
