@extends('layouts.app')
@section('title', 'Create Trade')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Create New Trade</h2>
            <p class="text-gray-600 mt-1">Add a new vocational training trade</p>
        </div>
        <a href="{{ route('admin.trades.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800 font-medium mb-2"><i class="fas fa-exclamation-circle mr-2"></i>Please fix the following errors:</p>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.trades.store') }}" method="POST" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-tools mr-2 text-blue-600"></i>
                Trade Information
            </h3>
        </div>

        <div class="space-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Trade Name <span class="text-red-600">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                       value="{{ old('name') }}" placeholder="e.g., Electrician">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                    Trade Code <span class="text-red-600">*</span>
                </label>
                <input type="text" name="code" id="code" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code') border-red-500 @enderror"
                       value="{{ old('code') }}" placeholder="e.g., ELEC-01">
                @error('code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                    Category
                </label>
                <input type="text" name="category" id="category"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category') border-red-500 @enderror"
                       value="{{ old('category') }}" placeholder="e.g., Electrical, Construction, Hospitality">
                @error('category')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="duration_months" class="block text-sm font-medium text-gray-700 mb-1">
                    Duration (months)
                </label>
                <input type="number" name="duration_months" id="duration_months" min="1"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('duration_months') border-red-500 @enderror"
                       value="{{ old('duration_months') }}" placeholder="e.g., 3">
                @error('duration_months')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Description
                </label>
                <textarea name="description" id="description" rows="4"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Brief description of the trade...">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <a href="{{ route('admin.trades.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>Create Trade
            </button>
        </div>
    </form>
</div>
@endsection
