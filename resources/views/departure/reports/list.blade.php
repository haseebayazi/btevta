@extends('layouts.app')
@section('title', 'Departure List Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Departure List Report</h1>
        <a href="{{ route('departure.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Departures
        </a>
    </div>

    <!-- Report Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-5 gap-4">
            <div>
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" value="{{ $validated['from_date'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" value="{{ $validated['to_date'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Trade</label>
                <select name="trade_id" class="form-input">
                    <option value="">All Trades</option>
                    @foreach($trades as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['trade_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">OEP</label>
                <select name="oep_id" class="form-input">
                    <option value="">All OEPs</option>
                    @foreach($oeps as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['oep_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
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
                        <th class="px-4 py-3 text-left">Candidate Name</th>
                        <th class="px-4 py-3 text-left">Trade</th>
                        <th class="px-4 py-3 text-left">OEP</th>
                        <th class="px-4 py-3 text-left">Campus</th>
                        <th class="px-4 py-3 text-center">Departure Date</th>
                        <th class="px-4 py-3 text-center">Iqama</th>
                        <th class="px-4 py-3 text-center">Absher</th>
                        <th class="px-4 py-3 text-center">Salary</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($departures as $departure)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $departure->candidate->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->trade->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->oep->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->campus->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ $departure->departure_date ? \Carbon\Carbon::parse($departure->departure_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->iqama_number)
                                <span class="badge badge-success">Registered</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->absher_registered)
                                <span class="badge badge-success">Registered</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->salary_confirmed)
                                <span class="badge badge-success">Confirmed</span>
                            @elseif($departure->first_salary_date)
                                <span class="badge badge-info">Received</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No departures found matching the criteria.</td>
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
