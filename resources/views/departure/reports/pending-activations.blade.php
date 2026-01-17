@extends('layouts.app')
@section('title', 'Pending Activations Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Pending Iqama/Absher Activations</h1>
        <a href="{{ route('departure.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Departures
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">Pending Iqama Registration</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $stats['pending_iqama'] }}</p>
        </div>
        <div class="card bg-orange-50">
            <p class="text-sm text-orange-800">Pending Absher Registration</p>
            <p class="text-3xl font-bold text-orange-900">{{ $stats['pending_absher'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Pending Type</label>
                <select name="type" class="form-input">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Pending</option>
                    <option value="iqama" {{ $type === 'iqama' ? 'selected' : '' }}>Pending Iqama Only</option>
                    <option value="absher" {{ $type === 'absher' ? 'selected' : '' }}>Pending Absher Only</option>
                </select>
            </div>
            <div>
                <label class="form-label">OEP</label>
                <select name="oep_id" class="form-input">
                    <option value="">All OEPs</option>
                    @foreach($oeps as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
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
                        <th class="px-4 py-3 text-left">TheLeap ID</th>
                        <th class="px-4 py-3 text-left">Candidate Name</th>
                        <th class="px-4 py-3 text-left">Trade</th>
                        <th class="px-4 py-3 text-left">OEP</th>
                        <th class="px-4 py-3 text-center">Departure Date</th>
                        <th class="px-4 py-3 text-center">Days Since Departure</th>
                        <th class="px-4 py-3 text-center">Iqama Status</th>
                        <th class="px-4 py-3 text-center">Absher Status</th>
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
                        <td class="px-4 py-3 text-center">
                            @if($departure->departure_date)
                                @php $days = \Carbon\Carbon::parse($departure->departure_date)->diffInDays(now()); @endphp
                                <span class="badge {{ $days > 30 ? 'badge-danger' : ($days > 14 ? 'badge-warning' : 'badge-info') }}">
                                    {{ $days }} days
                                </span>
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->iqama_number)
                                <span class="badge badge-success">{{ $departure->iqama_number }}</span>
                            @else
                                <span class="badge badge-danger">Not Registered</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($departure->absher_registered)
                                <span class="badge badge-success">Registered</span>
                            @else
                                <span class="badge badge-danger">Not Registered</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('departure.show', $departure) }}" class="btn btn-sm btn-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">No pending activations found.</td>
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
