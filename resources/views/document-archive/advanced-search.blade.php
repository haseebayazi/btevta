@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Advanced Document Search</h1>
            <p class="text-gray-600 mt-1">Search with multiple filters and criteria</p>
        </div>
        <a href="{{ route('document-archive.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Documents
        </a>
    </div>

    <!-- Search Form -->
    <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Search Filters</h2>
        <form method="GET" action="{{ route('document-archive.advanced-search') }}" class="space-y-4">
            <!-- Keyword Search -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">
                        Keyword
                    </label>
                    <input
                        type="text"
                        name="keyword"
                        id="keyword"
                        value="{{ $validated['keyword'] ?? '' }}"
                        placeholder="Search in document name, number, description, candidate name, CNIC..."
                        class="form-input w-full"
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        Searches across document name, number, description, candidate name, and CNIC
                    </p>
                </div>
            </div>

            <!-- Document Type and Category -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Document Type
                    </label>
                    <select name="document_type" id="document_type" class="form-select w-full">
                        <option value="">All Types</option>
                        @foreach($filterOptions['document_types'] as $type)
                            <option value="{{ $type }}" {{ ($validated['document_type'] ?? '') === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="document_category" class="block text-sm font-medium text-gray-700 mb-1">
                        Document Category
                    </label>
                    <select name="document_category" id="document_category" class="form-select w-full">
                        <option value="">All Categories</option>
                        @foreach($filterOptions['document_categories'] as $category)
                            <option value="{{ $category }}" {{ ($validated['document_category'] ?? '') === $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Campus and Uploader -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(auth()->user()->role !== 'campus_admin')
                <div>
                    <label for="campus_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Campus
                    </label>
                    <select name="campus_id" id="campus_id" class="form-select w-full">
                        <option value="">All Campuses</option>
                        @foreach($filterOptions['campuses'] as $id => $name)
                            <option value="{{ $id }}" {{ ($validated['campus_id'] ?? '') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label for="uploaded_by" class="block text-sm font-medium text-gray-700 mb-1">
                        Uploaded By
                    </label>
                    <select name="uploaded_by" id="uploaded_by" class="form-select w-full">
                        <option value="">Any User</option>
                        @foreach($filterOptions['uploaders'] as $id => $name)
                            <option value="{{ $id }}" {{ ($validated['uploaded_by'] ?? '') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tags
                </label>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    @foreach($filterOptions['tags'] as $tag)
                        <label class="flex items-center space-x-2 cursor-pointer p-2 rounded hover:bg-gray-50">
                            <input
                                type="checkbox"
                                name="tag_ids[]"
                                value="{{ $tag->id }}"
                                {{ in_array($tag->id, $validated['tag_ids'] ?? []) ? 'checked' : '' }}
                                class="form-checkbox"
                            >
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                  style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                {{ $tag->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Documents matching ANY of the selected tags will be shown
                </p>
            </div>

            <!-- Upload Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="upload_date_from" class="block text-sm font-medium text-gray-700 mb-1">
                        Upload Date From
                    </label>
                    <input
                        type="date"
                        name="upload_date_from"
                        id="upload_date_from"
                        value="{{ $validated['upload_date_from'] ?? '' }}"
                        class="form-input w-full"
                    >
                </div>

                <div>
                    <label for="upload_date_to" class="block text-sm font-medium text-gray-700 mb-1">
                        Upload Date To
                    </label>
                    <input
                        type="date"
                        name="upload_date_to"
                        id="upload_date_to"
                        value="{{ $validated['upload_date_to'] ?? '' }}"
                        class="form-input w-full"
                    >
                </div>
            </div>

            <!-- Expiry Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="expiry_date_from" class="block text-sm font-medium text-gray-700 mb-1">
                        Expiry Date From
                    </label>
                    <input
                        type="date"
                        name="expiry_date_from"
                        id="expiry_date_from"
                        value="{{ $validated['expiry_date_from'] ?? '' }}"
                        class="form-input w-full"
                    >
                </div>

                <div>
                    <label for="expiry_date_to" class="block text-sm font-medium text-gray-700 mb-1">
                        Expiry Date To
                    </label>
                    <input
                        type="date"
                        name="expiry_date_to"
                        id="expiry_date_to"
                        value="{{ $validated['expiry_date_to'] ?? '' }}"
                        class="form-input w-full"
                    >
                </div>
            </div>

            <!-- File Type and Expiry Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="file_type" class="block text-sm font-medium text-gray-700 mb-1">
                        File Type
                    </label>
                    <select name="file_type" id="file_type" class="form-select w-full">
                        <option value="">All File Types</option>
                        @foreach($filterOptions['file_types'] as $type)
                            <option value="{{ $type }}" {{ ($validated['file_type'] ?? '') === $type ? 'selected' : '' }}>
                                {{ strtoupper($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="has_expiry"
                            value="1"
                            {{ ($validated['has_expiry'] ?? false) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="text-sm font-medium text-gray-700">Has Expiry Date</span>
                    </label>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_expired"
                            value="1"
                            {{ ($validated['is_expired'] ?? false) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="text-sm font-medium text-gray-700">Is Expired</span>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('document-archive.advanced-search') }}" class="btn btn-secondary">
                    <i class="fas fa-redo mr-2"></i>Clear Filters
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search mr-2"></i>Search Documents
                </button>
            </div>
        </form>
    </div>

    <!-- Search Results -->
    @if(request()->hasAny(['keyword', 'document_type', 'document_category', 'campus_id', 'uploaded_by', 'tag_ids', 'upload_date_from', 'file_type']))
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Search Results</h2>
                <span class="text-sm text-gray-600">
                    {{ $documents->total() }} document(s) found
                </span>
            </div>

            @if($documents->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Document
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type/Category
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Candidate
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tags
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Uploaded
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Expiry
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($documents as $document)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-{{ $document->file_type === 'pdf' ? 'pdf text-red-500' : ($document->file_type === 'image' ? 'image text-blue-500' : 'alt text-gray-500') }} mr-2"></i>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $document->document_name }}
                                                </div>
                                                @if($document->document_number)
                                                    <div class="text-xs text-gray-500">
                                                        #{{ $document->document_number }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $document->document_type }}</div>
                                        <div class="text-xs text-gray-500">{{ $document->document_category }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($document->candidate)
                                            <a href="{{ route('candidates.show', $document->candidate) }}" class="text-sm text-blue-600 hover:underline">
                                                {{ $document->candidate->name }}
                                            </a>
                                        @else
                                            <span class="text-sm text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($document->tags as $tag)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $document->uploaded_at ? $document->uploaded_at->format('Y-m-d') : 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ $document->uploader ? $document->uploader->name : 'Unknown' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($document->expiry_date)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                {{ $document->expiry_date < now() ? 'bg-red-100 text-red-800' :
                                                   ($document->expiry_date < now()->addDays(30) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                                {{ $document->expiry_date->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">No expiry</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="{{ route('document-archive.show', $document) }}" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No documents found matching your search criteria.</p>
                    <p class="text-gray-400 text-sm mt-2">Try adjusting your filters or search terms.</p>
                </div>
            @endif
        </div>
    @else
        <div class="card">
            <div class="text-center py-12">
                <i class="fas fa-filter text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Apply filters above to search documents</p>
                <p class="text-gray-400 text-sm mt-2">Use one or more filters to find specific documents in the archive.</p>
            </div>
        </div>
    @endif
</div>
@endsection
