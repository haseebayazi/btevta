@extends('layouts.app')
@section('title', 'Edit Success Story')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.success-stories.show', $successStory) }}"
           class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Back to Story
        </a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            <i class="fas fa-edit text-indigo-500 mr-2"></i>Edit Success Story
        </h1>
    </div>


    <form method="POST" action="{{ route('admin.success-stories.update', $successStory) }}"
          enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')

        {{-- Candidate (read-only) --}}
        <div class="bg-white rounded-lg border shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                <i class="fas fa-user-circle text-blue-500 mr-2"></i>Candidate
            </h2>
            <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-user text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">{{ $successStory->candidate->name }}</p>
                    <p class="text-sm text-gray-500">BTEVTA ID: {{ $successStory->candidate->btevta_id }}</p>
                </div>
            </div>
        </div>

        {{-- Story Details --}}
        <div class="bg-white rounded-lg border shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                <i class="fas fa-pen text-green-500 mr-2"></i>Story Details
            </h2>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Story Type</label>
                    <select name="story_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">— Select type —</option>
                        @foreach($storyTypes as $val => $label)
                        <option value="{{ $val }}" {{ old('story_type', $successStory->story_type?->value) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Headline</label>
                    <input type="text" name="headline"
                           value="{{ old('headline', $successStory->headline) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Story / Written Note <span class="text-red-500">*</span></label>
                <textarea name="written_note" rows="5" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none @error('written_note') border-red-500 @enderror">{{ old('written_note', $successStory->written_note) }}</textarea>
                @error('written_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 flex items-center gap-3">
                <input type="checkbox" name="is_featured" id="is_featured" value="1"
                       {{ old('is_featured', $successStory->is_featured) ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 rounded">
                <label for="is_featured" class="text-sm font-medium text-gray-700">
                    <i class="fas fa-star text-yellow-500 mr-1"></i> Featured story
                </label>
            </div>
        </div>

        {{-- Employment Outcome --}}
        <div class="bg-white rounded-lg border shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                <i class="fas fa-briefcase text-indigo-500 mr-2"></i>Employment Outcome
            </h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employer Name</label>
                    <input type="text" name="employer_name"
                           value="{{ old('employer_name', $successStory->employer_name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position Achieved</label>
                    <input type="text" name="position_achieved"
                           value="{{ old('position_achieved', $successStory->position_achieved) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destination Country</label>
                    <select name="country_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">— Select country —</option>
                        @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                                {{ old('country_id', $successStory->country_id) == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Start Date</label>
                    <input type="date" name="employment_start_date"
                           value="{{ old('employment_start_date', $successStory->employment_start_date?->format('Y-m-d')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary Achieved</label>
                    <input type="number" name="salary_achieved"
                           value="{{ old('salary_achieved', $successStory->salary_achieved) }}"
                           min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <select name="salary_currency"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        @foreach(['SAR', 'AED', 'USD', 'EUR', 'GBP', 'PKR'] as $curr)
                        <option value="{{ $curr }}" {{ old('salary_currency', $successStory->salary_currency) == $curr ? 'selected' : '' }}>{{ $curr }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Days to Employment</label>
                    <input type="number" name="time_to_employment_days"
                           value="{{ old('time_to_employment_days', $successStory->time_to_employment_days) }}"
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.success-stories.show', $successStory) }}"
               class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors flex items-center gap-2">
                <i class="fas fa-save"></i> Update Story
            </button>
        </div>
    </form>
</div>
@endsection
