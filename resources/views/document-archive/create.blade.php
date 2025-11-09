@extends('layouts.app')
@section('title', 'Upload Document')
@section('content')
<div class="container mx-auto px-4 py-6">
<h1 class="text-3xl font-bold mb-6">Upload New Document</h1>
<form method="POST" action="{{ route('document-archive.store') }}" enctype="multipart/form-data" class="card max-w-3xl">
@csrf
<div class="space-y-4">
<div>
<label class="form-label required">Document Title</label>
<input type="text" name="title" class="form-input" required>
</div>
<div>
<label class="form-label required">Category</label>
<select name="category" class="form-input" required>
<option value="">Select Category</option>
<option value="passport">Passport</option>
<option value="visa">Visa</option>
<option value="medical">Medical</option>
<option value="training">Training</option>
<option value="other">Other</option>
</select>
</div>
<div>
<label class="form-label required">Upload File</label>
<input type="file" name="document" class="form-input" required>
</div>
<div>
<label class="form-label">Expiry Date</label>
<input type="date" name="expiry_date" class="form-input">
</div>
<div>
<label class="form-label">Description</label>
<textarea name="description" rows="3" class="form-input"></textarea>
</div>
</div>
<div class="flex justify-end gap-3 mt-6">
<button type="submit" class="btn btn-primary">Upload Document</button>
</div>
</form>
</div>
@endsection