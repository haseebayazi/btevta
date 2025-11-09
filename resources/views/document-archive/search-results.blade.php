@extends('layouts.app')
@section('title', 'Document Search Results')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Document Search Results</h1>
        <p class="text-gray-600 mt-2">
            Found {{ $documents->count() }} document(s) matching your search
            @if(request('term'))
                for "<strong>{{ request('term') }}</strong>"
            @endif
        </p>
    </div>

    <!-- Search Form -->
    <div class="card mb-6">
        <form action="{{ route('document-archive.search') }}" method="GET">
            <div class="grid md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <input type="text"
                           name="term"
                           class="form-control"
                           placeholder="Search documents..."
                           value="{{ request('term') }}"
                           required>
                </div>
                <div>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <option value="candidate" {{ request('category') == 'candidate' ? 'selected' : '' }}>Candidate</option>
                        <option value="campus" {{ request('category') == 'campus' ? 'selected' : '' }}>Campus</option>
                        <option value="oep" {{ request('category') == 'oep' ? 'selected' : '' }}>OEP</option>
                        <option value="contract" {{ request('category') == 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="legal" {{ request('category') == 'legal' ? 'selected' : '' }}>Legal</option>
                        <option value="certificate" {{ request('category') == 'certificate' ? 'selected' : '' }}>Certificate</option>
                        <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="{{ route('document-archive.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Search Results -->
    <div class="space-y-3">
        @forelse($documents as $document)
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex items-start gap-4">
                <!-- File Icon -->
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 bg-blue-100 rounded flex items-center justify-center">
                        @if(in_array($document->file_type, ['pdf']))
                            <i class="fas fa-file-pdf text-3xl text-red-500"></i>
                        @elseif(in_array($document->file_type, ['doc', 'docx']))
                            <i class="fas fa-file-word text-3xl text-blue-500"></i>
                        @elseif(in_array($document->file_type, ['xls', 'xlsx']))
                            <i class="fas fa-file-excel text-3xl text-green-500"></i>
                        @elseif(in_array($document->file_type, ['jpg', 'jpeg', 'png']))
                            <i class="fas fa-file-image text-3xl text-purple-500"></i>
                        @else
                            <i class="fas fa-file text-3xl text-gray-500"></i>
                        @endif
                    </div>
                </div>

                <!-- Document Info -->
                <div class="flex-1">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-bold mb-1">
                                <a href="{{ route('document-archive.show', $document) }}" class="text-blue-600 hover:underline">
                                    {{ $document->document_name }}
                                </a>
                            </h3>
                            <div class="flex flex-wrap gap-2 mb-2">
                                <span class="badge badge-primary">{{ ucfirst($document->document_category) }}</span>
                                <span class="badge badge-secondary">{{ $document->document_type }}</span>
                                @if($document->document_number)
                                <span class="badge badge-info">{{ $document->document_number }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('document-archive.view', $document) }}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('document-archive.download', $document) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 space-y-1">
                        @if($document->candidate)
                        <p>
                            <i class="fas fa-user mr-1"></i>
                            <strong>Candidate:</strong> {{ $document->candidate->name }} ({{ $document->candidate->cnic }})
                        </p>
                        @endif
                        @if($document->description)
                        <p>
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ Str::limit($document->description, 150) }}
                        </p>
                        @endif
                        <p>
                            <i class="fas fa-calendar mr-1"></i>
                            <strong>Uploaded:</strong> {{ $document->uploaded_at ? $document->uploaded_at->format('M d, Y H:i') : 'N/A' }}
                            @if($document->uploader)
                                by {{ $document->uploader->name }}
                            @endif
                        </p>
                        @if($document->issue_date || $document->expiry_date)
                        <p>
                            <i class="fas fa-calendar-check mr-1"></i>
                            @if($document->issue_date)
                                <strong>Issue:</strong> {{ $document->issue_date->format('M d, Y') }}
                            @endif
                            @if($document->expiry_date)
                                @if($document->issue_date) | @endif
                                <strong>Expiry:</strong>
                                <span class="{{ $document->expiry_date->isPast() ? 'text-red-600 font-semibold' : '' }}">
                                    {{ $document->expiry_date->format('M d, Y') }}
                                    @if($document->expiry_date->isPast())
                                        <i class="fas fa-exclamation-triangle ml-1"></i>
                                    @endif
                                </span>
                            @endif
                        </p>
                        @endif
                        <p>
                            <i class="fas fa-hdd mr-1"></i>
                            <strong>Size:</strong> {{ $document->file_size ? number_format($document->file_size / 1024, 2) : '0' }} KB
                            | <strong>Type:</strong> {{ strtoupper($document->file_type) }}
                        </p>
                    </div>

                    @if($document->tags)
                    <div class="mt-2">
                        <i class="fas fa-tags text-gray-400 mr-1"></i>
                        @foreach(explode(',', $document->tags) as $tag)
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded mr-1">
                                {{ trim($tag) }}
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="card text-center py-16">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500 mb-2">No documents found</p>
            <p class="text-gray-400">Try adjusting your search criteria</p>
            <a href="{{ route('document-archive.index') }}" class="btn btn-primary mt-4">
                <i class="fas fa-arrow-left mr-2"></i>Back to All Documents
            </a>
        </div>
        @endforelse
    </div>

    <!-- Results Summary -->
    @if($documents->count() > 0)
    <div class="card mt-6">
        <div class="grid md:grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-blue-600">{{ $documents->count() }}</p>
                <p class="text-sm text-gray-600">Total Results</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600">
                    {{ $documents->filter(fn($d) => $d->expiry_date && $d->expiry_date->isFuture())->count() }}
                </p>
                <p class="text-sm text-gray-600">Valid Documents</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-600">
                    {{ $documents->filter(fn($d) => $d->expiry_date && $d->expiry_date->isPast())->count() }}
                </p>
                <p class="text-sm text-gray-600">Expired Documents</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
