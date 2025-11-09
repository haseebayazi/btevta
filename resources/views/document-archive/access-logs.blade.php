@extends('layouts.app')
@section('title', 'Access Logs')
@section('content')
<div class="container mx-auto px-4 py-6">
<h1 class="text-3xl font-bold mb-6">Document Access Logs</h1>
<div class="card">
<table class="min-w-full">
<thead class="bg-gray-50">
<tr>
<th class="px-4 py-3 text-left">User</th>
<th class="px-4 py-3 text-left">Document</th>
<th class="px-4 py-3 text-center">Action</th>
<th class="px-4 py-3 text-center">Date/Time</th>
</tr>
</thead>
<tbody>
@foreach($logs as $log)
<tr>
<td class="px-4 py-3">{{ $log->user->name }}</td>
<td class="px-4 py-3">{{ $log->document->title }}</td>
<td class="px-4 py-3 text-center"><span class="badge badge-info">{{ $log->action }}</span></td>
<td class="px-4 py-3 text-center text-sm">{{ $log->created_at->format('M d, Y H:i') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
@endsection