@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Correspondence Management</h2>
        <a href="{{ route('correspondence.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>New Correspondence
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Total</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $correspondenceStats['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Incoming</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $correspondenceStats['incoming'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Outgoing</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ $correspondenceStats['outgoing'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Pending Reply</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $correspondenceStats['pending_reply'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Correspondence Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Recent Correspondence</h3>
        </div>
        
        <div class="overflow-x-auto">
            @if($correspondences->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Ref #</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Date</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Type</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Subject</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">From/To</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($correspondences as $corr)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-semibold">{{ $corr->reference_number }}</td>
                                <td class="px-6 py-4">{{ $corr->correspondence_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                        {{ $corr->correspondence_type === 'incoming' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                        {{ ucfirst($corr->correspondence_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ Str::limit($corr->subject, 40) }}</td>
                                <td class="px-6 py-4">{{ $corr->from_to ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    @if($corr->requires_reply && !$corr->replied)
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            Pending Reply
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            Replied
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('correspondence.show', $corr->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $correspondences->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No correspondence records found</p>
            @endif
        </div>
    </div>
</div>
@endsection