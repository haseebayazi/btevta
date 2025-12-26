@extends('layouts.app')
@section('title', 'Salary Disbursement Status')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Salary Disbursement Status Report</h1>
        <a href="{{ route('departure.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Departures
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Departed</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total_departed'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">Salary Confirmed</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['salary_confirmed'] }}</p>
        </div>
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">Pending Confirmation</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $stats['salary_pending'] }}</p>
        </div>
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Not Received</p>
            <p class="text-3xl font-bold text-red-900">{{ $stats['salary_not_received'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Total Confirmed Amount</p>
            <p class="text-3xl font-bold text-purple-900">{{ number_format($stats['total_salary_amount']) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-5 gap-4">
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Confirmation</option>
                    <option value="not_received" {{ $status === 'not_received' ? 'selected' : '' }}>Not Received</option>
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
                        <th class="px-4 py-3 text-left">Candidate Name</th>
                        <th class="px-4 py-3 text-left">Trade</th>
                        <th class="px-4 py-3 text-left">OEP</th>
                        <th class="px-4 py-3 text-center">Departure Date</th>
                        <th class="px-4 py-3 text-center">First Salary Date</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($departures as $departure)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $departure->candidate->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->trade->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $departure->candidate->oep->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ $departure->departure_date ? \Carbon\Carbon::parse($departure->departure_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">{{ $departure->first_salary_date ? \Carbon\Carbon::parse($departure->first_salary_date)->format('d M Y') : '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($departure->salary_amount)
                                {{ $departure->salary_currency ?? 'SAR' }} {{ number_format($departure->salary_amount) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->salary_confirmed)
                                <span class="badge badge-success">Confirmed</span>
                            @elseif($departure->first_salary_date)
                                <span class="badge badge-warning">Pending Confirmation</span>
                            @else
                                <span class="badge badge-danger">Not Received</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('departure.show', $departure) }}" class="btn btn-sm btn-primary">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No records found matching the criteria.</td>
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
