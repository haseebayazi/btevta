@extends('layouts.app')
@section('title', 'New Correspondence')
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">New Correspondence</h2>
            <p class="text-gray-600 mt-1">Register new incoming or outgoing correspondence</p>
        </div>
        <a href="{{ route('correspondence.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <form action="{{ route('correspondence.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-envelope mr-2 text-blue-600"></i>
                Correspondence Information
            </h3>
        </div>

        <!-- Row 1: Reference, Date, Type -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reference Number <span class="text-red-600">*</span>
                </label>
                <input type="text" name="reference_number" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('reference_number') border-red-500 @enderror"
                       placeholder="e.g., CORR-2025-001" value="{{ old('reference_number') }}">
                @error('reference_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Date <span class="text-red-600">*</span>
                </label>
                <input type="date" name="correspondence_date" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('correspondence_date') border-red-500 @enderror"
                       value="{{ old('correspondence_date', date('Y-m-d')) }}">
                @error('correspondence_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Type <span class="text-red-600">*</span>
                </label>
                <select name="correspondence_type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('correspondence_type') border-red-500 @enderror">
                    <option value="">Select Type</option>
                    <option value="incoming" {{ old('correspondence_type') === 'incoming' ? 'selected' : '' }}>
                        📥 Incoming
                    </option>
                    <option value="outgoing" {{ old('correspondence_type') === 'outgoing' ? 'selected' : '' }}>
                        📤 Outgoing
                    </option>
                </select>
                @error('correspondence_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Row 2: From/To, Campus -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    From/To <span class="text-red-600">*</span>
                </label>
                <input type="text" name="from_to" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('from_to') border-red-500 @enderror"
                       placeholder="Organization/Department name" value="{{ old('from_to') }}">
                @error('from_to')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Campus (if applicable)
                </label>
                <select name="campus_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('campus_id') border-red-500 @enderror">
                    <option value="">🏢 Headquarters</option>
                    @if(isset($campuses))
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('campus_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Subject -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Subject <span class="text-red-600">*</span>
            </label>
            <input type="text" name="subject" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subject') border-red-500 @enderror"
                   placeholder="Brief subject of correspondence" value="{{ old('subject') }}">
            @error('subject')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Description/Content
            </label>
            <textarea name="description" rows="5"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                      placeholder="Detailed description of the correspondence...">{{ old('description') }}</textarea>
            @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Row 3: Requires Reply, Deadline -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-center">
                <input type="checkbox" id="requires_reply" name="requires_reply" value="1"
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                       {{ old('requires_reply') ? 'checked' : '' }}>
                <label for="requires_reply" class="ml-2 text-sm font-medium text-gray-700">
                    Requires Reply
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reply Deadline (if applicable)
                </label>
                <input type="date" name="reply_deadline"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('reply_deadline') border-red-500 @enderror"
                       value="{{ old('reply_deadline') }}">
                @error('reply_deadline')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- File Attachments -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Attach Files
            </label>
            <input type="file" name="attachments[]" multiple
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <p class="mt-1 text-xs text-gray-500">
                <i class="fas fa-info-circle"></i> You can attach multiple files (PDF, images, documents)
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <a href="{{ route('correspondence.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>Save Correspondence
            </button>
        </div>
    </form>
</div>
@endsection
