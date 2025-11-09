@extends('layouts.app')
@section('title', 'Edit Document')
@section('content')
<div class="container mx-auto px-4 py-6">
<h1 class="text-3xl font-bold mb-6">Edit Document Metadata</h1>
<form method="POST" action="{{ route('document-archive.update', $document) }}" class="card max-w-3xl">
@csrf
@method('PUT')
<div class="space-y-4">
<div>
<label class="form-label">Title</label>
<input type="text" name="title" value="{{ $document->title }}" class="form-input">
</div>
<div>
<label class="form-label">Category</label>
<select name="category" class="form-input">
<option value="passport">Passport</option>
<option value="visa">Visa</option>
<option value="medical">Medical</option>
</select>
</div>
<div>
<label class="form-label">Expiry Date</label>
<input type="date" name="expiry_date" value="{{ $document->expiry_date->format('Y-m-d') }}" class="form-input">
</div>
</div>
<div class="flex justify-end gap-3 mt-6">
<button type="submit" class="btn btn-primary">Update</button>
</div>
</form>
</div>
@endsection