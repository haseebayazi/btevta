@extends('layouts.app')
@section('title', 'Candidate Documents')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Documents - {{ $candidate->name }}</h1>
    
    <div class="card">
        @foreach($documentsByCategory as $category => $docs)
        <div class="mb-6">
            <h3 class="text-lg font-bold mb-3">{{ ucfirst($category) }} ({{ $docs->count() }})</h3>
            <div class="space-y-2">
                @foreach($docs as $doc)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file-{{ $doc->icon }} text-2xl text-gray-500"></i>
                        <div>
                            <p class="font-medium">{{ $doc->title }}</p>
                            <p class="text-sm text-gray-600">{{ $doc->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('document-archive.show', $doc) }}" class="btn btn-sm btn-primary">
                        View
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection