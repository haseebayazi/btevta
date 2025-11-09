@extends('layouts.app')
@section('title', 'Visa Processing Details')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Visa Processing - {{ $visaProcessing->candidate->name }}</h1>
    
    <!-- Stage Progress -->
    <div class="card mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Processing Stages</h2>
            <span class="badge badge-info">Stage {{ $visaProcessing->current_stage }}/8</span>
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            @for($i = 1; $i <= 8; $i++)
            <div class="p-4 border rounded {{ $i <= $visaProcessing->current_stage ? 'bg-green-50 border-green-300' : 'bg-gray-50' }}">
                <p class="text-sm font-medium">Stage {{ $i }}</p>
                <p class="text-xs text-gray-600">{{ $stageNames[$i] ?? 'Stage ' . $i }}</p>
                @if($i <= $visaProcessing->current_stage)
                    <i class="fas fa-check-circle text-green-600 mt-2"></i>
                @endif
            </div>
            @endfor
        </div>
        <div class="mt-4">
            <a href="{{ route('visa-processing.timeline', $visaProcessing) }}" class="btn btn-primary">
                <i class="fas fa-timeline mr-2"></i>View Full Timeline
            </a>
        </div>
    </div>

    <!-- Documents -->
    <div class="card">
        <h3 class="text-lg font-bold mb-4">Documents</h3>
        @if($visaProcessing->documents->count() > 0)
            <div class="space-y-2">
                @foreach($visaProcessing->documents as $doc)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span>{{ $doc->name }}</span>
                    <a href="{{ $doc->url }}" class="text-blue-600" target="_blank">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500">No documents uploaded yet</p>
        @endif
    </div>
</div>
@endsection