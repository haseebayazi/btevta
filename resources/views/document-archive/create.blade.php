@extends('layouts.app')
@section('title', 'Upload Document')
@section('content')
<div class="container mx-auto px-4 py-6">
<h1 class="text-3xl font-bold mb-6">Upload New Document</h1>

@if ($errors->any())
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('document-archive.store') }}" enctype="multipart/form-data" class="card max-w-3xl">
@csrf
<div class="space-y-4">
<div>
<label class="form-label required">Document Title</label>
<input type="text" name="title" value="{{ old('title') }}" class="form-input @error('title') border-red-500 @enderror" required>
@error('title')
<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="form-label required">Category</label>
<select name="category" class="form-input @error('category') border-red-500 @enderror" required>
<option value="">Select Category</option>
<option value="passport" {{ old('category') == 'passport' ? 'selected' : '' }}>Passport</option>
<option value="visa" {{ old('category') == 'visa' ? 'selected' : '' }}>Visa</option>
<option value="medical" {{ old('category') == 'medical' ? 'selected' : '' }}>Medical</option>
<option value="training" {{ old('category') == 'training' ? 'selected' : '' }}>Training</option>
<option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
</select>
@error('category')
<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="form-label required">Upload File</label>
<input type="file" name="document" class="form-input @error('document') border-red-500 @enderror" required>
@error('document')
<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="form-label">Expiry Date</label>
<input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-input @error('expiry_date') border-red-500 @enderror">
@error('expiry_date')
<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="form-label">Description</label>
<textarea name="description" rows="3" class="form-input @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
@error('description')
<p class="text-red-500 text-sm mt-1">{{ $message }}</p>
@enderror
</div>
</div>
<div class="flex justify-end gap-3 mt-6">
<a href="{{ route('document-archive.index') }}" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary">Upload Document</button>
</div>
</form>
</div>
@endsection
