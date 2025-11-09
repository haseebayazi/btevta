@extends('layouts.app')
@section('title', 'Document Details')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">{{ $document->title }}</h1>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card mb-6">
                <h3 class="text-lg font-bold mb-4">Document Preview</h3>
                @if($document->is_image)
                    <img src="{{ $document->url }}" alt="{{ $document->title }}" class="max-w-full">
                @elseif($document->is_pdf)
                    <iframe src="{{ $document->url }}" class="w-full h-96"></iframe>
                @else
                    <a href="{{ $document->url }}" class="btn btn-primary" target="_blank">
                        <i class="fas fa-download mr-2"></i>Download Document
                    </a>
                @endif
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="card">
                <h3 class="text-lg font-bold mb-4">Document Information</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">Category</p>
                        <p class="font-semibold">{{ ucfirst($document->category) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">File Size</p>
                        <p class="font-semibold">{{ $document->file_size_formatted }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Uploaded By</p>
                        <p class="font-semibold">{{ $document->uploaded_by->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Upload Date</p>
                        <p class="font-semibold">{{ $document->created_at->format('M d, Y') }}</p>
                    </div>
                    @if($document->expiry_date)
                    <div>
                        <p class="text-gray-600">Expiry Date</p>
                        <p class="font-semibold {{ $document->is_expired ? 'text-red-600' : '' }}">
                            {{ $document->expiry_date->format('M d, Y') }}
                            @if($document->is_expired)
                                <span class="badge badge-danger ml-2">Expired</span>
                            @endif
                        </p>
                    </div>
                    @endif
                </div>
                <div class="mt-6 space-y-2">
                    <a href="{{ route('document-archive.edit', $document) }}" class="btn btn-secondary w-full">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                    <a href="{{ $document->url }}" class="btn btn-primary w-full" download>
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection