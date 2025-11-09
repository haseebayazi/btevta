@extends('layouts.app')
@section('title', 'Document Statistics')
@section('content')
<div class="container mx-auto px-4 py-6">
<h1 class="text-3xl font-bold mb-6">Document Archive Statistics</h1>
<div class="grid md:grid-cols-4 gap-4 mb-6">
<div class="card bg-blue-50"><p class="text-sm text-blue-800">Total Documents</p><p class="text-3xl font-bold text-blue-900">{{ $stats['total'] }}</p></div>
<div class="card bg-green-50"><p class="text-sm text-green-800">Active</p><p class="text-3xl font-bold text-green-900">{{ $stats['active'] }}</p></div>
<div class="card bg-red-50"><p class="text-sm text-red-800">Expired</p><p class="text-3xl font-bold text-red-900">{{ $stats['expired'] }}</p></div>
<div class="card bg-purple-50"><p class="text-sm text-purple-800">Storage Used</p><p class="text-3xl font-bold text-purple-900">{{ $stats['storage'] }}MB</p></div>
</div>
<div class="card"><h2 class="text-xl font-bold mb-4">Documents by Category</h2><canvas id="categoryChart" height="300"></canvas></div>
</div>
@endsection