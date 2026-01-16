@extends('layouts.app')
@section('title', 'Compare Versions - ' . $document->document_name)
@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-600 mb-2">
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
            <span class="mx-2">/</span>
            <a href="{{ route('document-archive.index') }}" class="hover:text-blue-600">Document Archive</a>
            <span class="mx-2">/</span>
            <a href="{{ route('document-archive.show', $document) }}" class="hover:text-blue-600">{{ $document->document_name }}</a>
            <span class="mx-2">/</span>
            <span>Version Comparison</span>
        </nav>
        <h1 class="text-3xl font-bold">Version Comparison</h1>
        <p class="text-gray-600 mt-1">{{ $document->document_name }}</p>
    </div>

    <!-- Version Info Header -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="card bg-blue-50">
            <h3 class="text-lg font-bold text-blue-900 mb-2">Version {{ $version1->version }}</h3>
            <p class="text-sm text-gray-700">
                <i class="fas fa-clock mr-1"></i>
                {{ $version1->uploaded_at ? $version1->uploaded_at->format('M d, Y H:i') : 'N/A' }}
            </p>
            <p class="text-sm text-gray-700">
                <i class="fas fa-user mr-1"></i>
                {{ $version1->uploader ? $version1->uploader->name : 'Unknown' }}
            </p>
            @if($version1->is_current_version)
                <span class="badge badge-success mt-2">Current Version</span>
            @endif
        </div>

        <div class="card bg-green-50">
            <h3 class="text-lg font-bold text-green-900 mb-2">Version {{ $version2->version }}</h3>
            <p class="text-sm text-gray-700">
                <i class="fas fa-clock mr-1"></i>
                {{ $version2->uploaded_at ? $version2->uploaded_at->format('M d, Y H:i') : 'N/A' }}
            </p>
            <p class="text-sm text-gray-700">
                <i class="fas fa-user mr-1"></i>
                {{ $version2->uploader ? $version2->uploader->name : 'Unknown' }}
            </p>
            @if($version2->is_current_version)
                <span class="badge badge-success mt-2">Current Version</span>
            @endif
        </div>
    </div>

    <!-- Metadata Comparison -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Metadata Comparison</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-1/4">Field</th>
                        <th class="px-4 py-3 text-left font-semibold w-3/8">Version {{ $version1->version }}</th>
                        <th class="px-4 py-3 text-left font-semibold w-3/8">Version {{ $version2->version }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($comparison['metadata'] as $field => $data)
                        <tr class="{{ $data['changed'] ? 'bg-yellow-50' : '' }}">
                            <td class="px-4 py-3 font-medium">
                                {{ ucwords(str_replace('_', ' ', $field)) }}
                                @if($data['changed'])
                                    <i class="fas fa-exclamation-circle text-yellow-600 ml-1" title="Changed"></i>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $data['changed'] ? 'bg-red-50' : '' }}">
                                {{ $data['v1'] }}
                            </td>
                            <td class="px-4 py-3 {{ $data['changed'] ? 'bg-green-50' : '' }}">
                                {{ $data['v2'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- File Previews (Side by Side) -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">File Preview</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <h3 class="font-semibold text-blue-900 mb-2">Version {{ $version1->version }}</h3>
                <div class="border rounded p-4 bg-gray-50 text-center">
                    @if(in_array($version1->file_type, ['pdf', 'jpg', 'jpeg', 'png', 'gif']))
                        <a href="{{ route('document-archive.view', $version1) }}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-eye mr-1"></i>View File
                        </a>
                    @else
                        <p class="text-gray-600">Preview not available</p>
                    @endif
                    <a href="{{ route('document-archive.download', $version1) }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-download mr-1"></i>Download
                    </a>
                </div>
            </div>

            <div>
                <h3 class="font-semibold text-green-900 mb-2">Version {{ $version2->version }}</h3>
                <div class="border rounded p-4 bg-gray-50 text-center">
                    @if(in_array($version2->file_type, ['pdf', 'jpg', 'jpeg', 'png', 'gif']))
                        <a href="{{ route('document-archive.view', $version2) }}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-eye mr-1"></i>View File
                        </a>
                    @else
                        <p class="text-gray-600">Preview not available</p>
                    @endif
                    <a href="{{ route('document-archive.download', $version2) }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-download mr-1"></i>Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="card bg-blue-50 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i>Comparison Summary
        </h3>
        <p class="text-gray-700">
            @php
                $changedCount = collect($comparison['metadata'])->where('changed', true)->count();
            @endphp
            <strong>{{ $changedCount }}</strong> field(s) changed between these versions.
        </p>
        @if($changedCount > 0)
            <p class="text-sm text-gray-600 mt-2">
                Fields with changes are highlighted in yellow with changed values shown in red (old) and green (new).
            </p>
        @endif
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
        <a href="{{ route('document-archive.versions', $document) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Back to Versions
        </a>
        <a href="{{ route('document-archive.show', $document) }}" class="btn btn-outline-secondary">
            <i class="fas fa-file mr-1"></i>View Current Document
        </a>
    </div>
</div>
@endsection
