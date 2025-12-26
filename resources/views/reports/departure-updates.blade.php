@extends('layouts.app')
@section('title', 'Salary & Post-Departure Updates')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Salary & Post-Departure Updates Report</h1>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Reports
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Departed</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total_departed'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Briefing Completed</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['briefing_completed'] }}</p>
            <p class="text-xs text-green-700">{{ $stats['total_departed'] > 0 ? round(($stats['briefing_completed'] / $stats['total_departed']) * 100, 1) : 0 }}%</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Iqama Registered</p>
            <p class="text-3xl font-bold text-purple-900">{{ $stats['iqama_registered'] }}</p>
            <p class="text-xs text-purple-700">{{ $stats['total_departed'] > 0 ? round(($stats['iqama_registered'] / $stats['total_departed']) * 100, 1) : 0 }}%</p>
        </div>
        <div class="card bg-indigo-50">
            <p class="text-sm text-indigo-800">Absher Registered</p>
            <p class="text-3xl font-bold text-indigo-900">{{ $stats['absher_registered'] }}</p>
            <p class="text-xs text-indigo-700">{{ $stats['total_departed'] > 0 ? round(($stats['absher_registered'] / $stats['total_departed']) * 100, 1) : 0 }}%</p>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-teal-50">
            <p class="text-sm text-teal-800">Qiwa Activated</p>
            <p class="text-3xl font-bold text-teal-900">{{ $stats['qiwa_activated'] }}</p>
            <p class="text-xs text-teal-700">{{ $stats['total_departed'] > 0 ? round(($stats['qiwa_activated'] / $stats['total_departed']) * 100, 1) : 0 }}%</p>
        </div>
        <div class="card bg-emerald-50">
            <p class="text-sm text-emerald-800">Salary Confirmed</p>
            <p class="text-3xl font-bold text-emerald-900">{{ $stats['salary_confirmed'] }}</p>
            <p class="text-xs text-emerald-700">{{ $stats['total_departed'] > 0 ? round(($stats['salary_confirmed'] / $stats['total_departed']) * 100, 1) : 0 }}%</p>
        </div>
        <div class="card bg-cyan-50">
            <p class="text-sm text-cyan-800">90-Day Compliance</p>
            <p class="text-3xl font-bold text-cyan-900">{{ $stats['compliance_verified'] }}</p>
            <p class="text-xs text-cyan-700">{{ $stats['total_departed'] > 0 ? round(($stats['compliance_verified'] / $stats['total_departed']) * 100, 1) : 0 }}%</p>
        </div>
        <div class="card bg-amber-50">
            <p class="text-sm text-amber-800">Total Salary Amount</p>
            <p class="text-3xl font-bold text-amber-900">{{ number_format($stats['total_salary_amount']) }}</p>
            <p class="text-xs text-amber-700">SAR</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="form-label">OEP</label>
                <select name="oep_id" class="form-input">
                    <option value="">All OEPs</option>
                    @foreach($oeps as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['oep_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" value="{{ $validated['from_date'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" value="{{ $validated['to_date'] ?? '' }}" class="form-input">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Filter</button>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">BTEVTA ID</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Trade</th>
                        <th class="px-4 py-3 text-left">OEP</th>
                        <th class="px-4 py-3 text-center">Departure</th>
                        <th class="px-4 py-3 text-center">Iqama</th>
                        <th class="px-4 py-3 text-center">Absher</th>
                        <th class="px-4 py-3 text-center">Qiwa</th>
                        <th class="px-4 py-3 text-center">Salary</th>
                        <th class="px-4 py-3 text-center">90-Day</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($departures as $departure)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $departure->candidate->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->trade->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->oep->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $departure->departure_date ? \Carbon\Carbon::parse($departure->departure_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->iqama_number)
                                <span class="badge badge-success" title="{{ $departure->iqama_number }}">Done</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->absher_registered)
                                <span class="badge badge-success">Done</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->qiwa_id)
                                <span class="badge badge-success" title="{{ $departure->qiwa_id }}">Active</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->salary_confirmed)
                                <span class="badge badge-success" title="SAR {{ number_format($departure->salary_amount ?? 0) }}">Confirmed</span>
                            @elseif($departure->first_salary_date)
                                <span class="badge badge-info">Received</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->ninety_day_report_submitted)
                                <span class="badge badge-success">Verified</span>
                            @else
                                @php
                                    $daysAgo = $departure->departure_date ? \Carbon\Carbon::parse($departure->departure_date)->diffInDays(now()) : 0;
                                @endphp
                                @if($daysAgo > 90)
                                    <span class="badge badge-danger">Overdue</span>
                                @elseif($daysAgo > 75)
                                    <span class="badge badge-warning">Due Soon</span>
                                @else
                                    <span class="badge badge-secondary">{{ 90 - $daysAgo }}d left</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500">No departure records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($departures->hasPages())
        <div class="px-4 py-3 border-t">
            {{ $departures->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
