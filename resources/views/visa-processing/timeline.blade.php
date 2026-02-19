@extends('layouts.app')
@section('title', 'Visa Processing Timeline')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold">Visa Processing Timeline</h1>
            <p class="text-gray-600 mt-1">{{ $candidate->name }} - {{ $candidate->cnic ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('visa-processing.show', $candidate) }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
            Back to Visa Process
        </a>
    </div>

    <!-- Timeline -->
    <div class="relative">
        <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-300"></div>

        <div class="space-y-8">
            @forelse($timeline as $event)
            <div class="relative pl-20">
                <div class="absolute left-0 top-0 w-16 h-16 rounded-full flex items-center justify-center
                    {{ ($event['completed'] ?? false) ? 'bg-green-500' : 'bg-gray-300' }}">
                    <span class="text-white text-xs font-bold">{{ $loop->iteration }}</span>
                </div>

                <div class="bg-white rounded-lg shadow p-4 {{ ($event['completed'] ?? false) ? 'border-l-4 border-green-500' : 'border-l-4 border-gray-300' }}">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-bold">{{ $event['stage'] }}</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ ($event['completed'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($event['status'] ?? 'pending') }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-600">
                        @if($event['date'] ?? null)
                        <p><span class="font-medium">Date:</span> {{ \Carbon\Carbon::parse($event['date'])->format('M d, Y') }}</p>
                        @endif
                        @if($event['remarks'] ?? null)
                        <p class="mt-1"><span class="font-medium">Notes:</span> {{ $event['remarks'] }}</p>
                        @endif
                        @if($event['visa_number'] ?? null)
                        <p class="mt-1"><span class="font-medium">Visa Number:</span> {{ $event['visa_number'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500">
                <p>No timeline events recorded yet.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
