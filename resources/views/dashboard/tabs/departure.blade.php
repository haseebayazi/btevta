@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Departure & Post-Arrival Tracking</h2>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm">Total Departed</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['total_departed'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm">Briefing Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['briefing_completed'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm">Ready to Depart</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ $stats['ready_to_depart'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm">Post-Arrival (90 Days)</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">{{ $stats['post_arrival_90'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Departure Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Departure Records</h3>
        
        <div class="overflow-x-auto">
            @if($departures->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">TheLeap ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Departure Date</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">OEP</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Briefing</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Iqama</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departures as $departure)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $departure->candidate->name }}</td>
                                <td class="px-6 py-4 font-mono">{{ $departure->candidate->btevta_id }}</td>
                                <td class="px-6 py-4">{{ $departure->departure_date?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $departure->oep->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    @if($departure->briefing_completed)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>Completed
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($departure->iqama_number)
                                        <span class="font-mono text-green-600">{{ $departure->iqama_number }}</span>
                                    @else
                                        <span class="text-gray-400">Not provided</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('departure.show', $departure->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $departures->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No departure records found</p>
            @endif
        </div>
    </div>
</div>
@endsection