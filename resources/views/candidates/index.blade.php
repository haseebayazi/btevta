{{-- ============================================ --}}
{{-- File: resources/views/candidates/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">All Candidates</h2>
        <div class="flex space-x-3">
            <a href="{{ route('import.candidates.form') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-file-import mr-2"></i>Import
            </a>
            <a href="{{ route('candidates.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add New
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <input type="text" name="search" placeholder="Search..." 
                   class="px-4 py-2 border rounded-lg" value="{{ request('search') }}">
            
            <select name="status" class="px-4 py-2 border rounded-lg">
                <option value="">All Status</option>
                @foreach(\App\Enums\CandidateStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>