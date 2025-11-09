@extends('layouts.app')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Candidate Screening</h2>
        <a href="{{ route('screening.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Log Screening
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Call 1: Document Collection</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $pendingCall1 ?? 0 }}</p>
            <p class="text-sm text-gray-600 mt-2">Pending candidates</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Call 2: Registration</h3>
            <p class="text-3xl font-bold text-green-600">{{ $pendingCall2 ?? 0 }}</p>
            <p class="text-sm text-gray-600 mt-2">Pending calls</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Call 3: Confirmation</h3>
            <p class="text-3xl font-bold text-purple-600">{{ $pendingCall3 ?? 0 }}</p>
            <p class="text-sm text-gray-600 mt-2">Pending calls</p>
        </div>
    </div>

    <!-- Screening Queue Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Screening Queue</h3>
        
        <div class="overflow-x-auto">
            @if($screeningQueue->count() > 0)
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">BTEVTA ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Calls Completed</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Last Updated</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($screeningQueue as $candidate)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $candidate->name }}</td>
                                <td class="px-6 py-4 font-mono">{{ $candidate->btevta_id }}</td>
                                <td class="px-6 py-4">{{ $candidate->campus->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-blue-600 h-2 rounded-full" 
                                                 style="width: {{ ($candidate->screenings_count / 3) * 100 }}%"></div>
                                        </div>
                                        <span class="font-semibold">{{ $candidate->screenings_count }}/3</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $candidate->updated_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('screening.edit', $candidate->id) }}" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">Log Call</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-4">
                    {{ $screeningQueue->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No candidates in screening queue</p>
            @endif
        </div>
    </div>
</div>
@endsection