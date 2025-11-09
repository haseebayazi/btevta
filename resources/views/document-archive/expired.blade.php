@extends('layouts.app')
@section('title', 'Expired Documents')
@section('content')
<div class="container mx-auto px-4 py-6">
<h1 class="text-3xl font-bold mb-6">Expired Documents</h1>
<div class="card">
<table class="min-w-full">
<thead class="bg-gray-50">
<tr>
<th class="px-4 py-3 text-left">Document</th>
<th class="px-4 py-3 text-center">Category</th>
<th class="px-4 py-3 text-center">Expired On</th>
<th class="px-4 py-3 text-center">Days Overdue</th>
<th class="px-4 py-3 text-right">Actions</th>
</tr>
</thead>
<tbody>
@foreach($expiredDocs as $doc)
<tr class="bg-red-50">
<td class="px-4 py-3 font-medium">{{ $doc->title }}</td>
<td class="px-4 py-3 text-center">{{ $doc->category }}</td>
<td class="px-4 py-3 text-center">{{ $doc->expiry_date->format('M d, Y') }}</td>
<td class="px-4 py-3 text-center"><span class="badge badge-danger">{{ $doc->days_overdue }}d</span></td>
<td class="px-4 py-3 text-right"><a href="{{ route('document-archive.show', $doc) }}" class="text-blue-600">View</a></td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
@endsection