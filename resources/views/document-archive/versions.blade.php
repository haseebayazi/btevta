@extends('layouts.app')
@section('title', 'Document Versions')
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Version History - {{ $document->title }}</h1>
    
    <div class="card">
        <div class="space-y-4">
            @foreach($document->versions as $version)
            <div class="flex items-center justify-between p-4 border rounded {{ $version->is_current ? 'bg-blue-50 border-blue-300' : 'bg-gray-50' }}">
                <div>
                    <p class="font-semibold">Version {{ $version->version_number }}
                        @if($version->is_current)
                            <span class="badge badge-primary ml-2">Current</span>
                        @endif
                    </p>
                    <p class="text-sm text-gray-600">{{ $version->created_at->format('M d, Y H:i') }} by {{ $version->uploaded_by->name }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ $version->url }}" class="btn btn-sm btn-secondary" target="_blank">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    @if(!$version->is_current)
                    <button onclick="restoreVersion({{ $version->id }})" class="btn btn-sm btn-primary">
                        <i class="fas fa-undo mr-1"></i>Restore
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
function restoreVersion(versionId) {
    if (confirm('Restore this version as the current version?')) {
        // Submit restoration
        window.location.href = `/documents/versions/${versionId}/restore`;
    }
}
</script>
@endpush
@endsection