@extends('layouts.app')
@section('title', 'Edit Training')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Edit Training</h2>
            <p class="text-gray-500 text-sm mt-1">Update batch assignment for {{ $candidate->name }}</p>
        </div>
        <a href="{{ route('training.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Back to Training
        </a>
    </div>

    <form method="POST" action="{{ route('training.update', $candidate) }}" class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Candidate</label>
            <p class="text-gray-900 font-semibold">{{ $candidate->name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Batch <span class="text-red-600">*</span>
            </label>
            <select name="batch_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm @error('batch_id') border-red-500 ring-1 ring-red-500 @enderror" required>
                <option value="">Select Batch</option>
                @foreach($batches as $batch)
                <option value="{{ $batch->id }}" {{ old('batch_id', $candidate->batch_id) == $batch->id ? 'selected' : '' }}>
                    {{ $batch->batch_number }}
                </option>
                @endforeach
            </select>
            @error('batch_id')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between pt-4 border-t">
            <a href="{{ route('training.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm text-gray-700">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm">
                <i class="fas fa-save mr-1"></i> Update
            </button>
        </div>
    </form>
</div>
@endsection
