@extends('layouts.app')
@section('title', 'Document Verification Status')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Document Verification Status</h1>
        <a href="{{ route('document-archive.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Documents
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <p class="text-sm text-blue-800">Total Candidates</p>
            <p class="text-3xl font-bold text-blue-900">{{ $stats['total_candidates'] }}</p>
        </div>
        <div class="card bg-green-50">
            <p class="text-sm text-green-800">With Documents</p>
            <p class="text-3xl font-bold text-green-900">{{ $stats['with_documents'] }}</p>
        </div>
        <div class="card bg-purple-50">
            <p class="text-sm text-purple-800">Complete Documents</p>
            <p class="text-3xl font-bold text-purple-900">{{ $stats['with_complete_documents'] }}</p>
        </div>
        <div class="card bg-indigo-50">
            <p class="text-sm text-indigo-800">Completion Rate</p>
            <p class="text-3xl font-bold text-indigo-900">{{ $stats['overall_completion_rate'] }}%</p>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="card mb-6">
        <form method="GET" class="flex gap-4 items-center">
            <label class="form-label mb-0">View By:</label>
            <select name="view_by" class="form-input w-48" onchange="this.form.submit()">
                <option value="campus" {{ $viewBy === 'campus' ? 'selected' : '' }}>Campus</option>
                <option value="oep" {{ $viewBy === 'oep' ? 'selected' : '' }}>OEP</option>
            </select>
        </form>
    </div>

    <!-- Results Table -->
    <div class="card">
        <h3 class="text-lg font-bold mb-4">Verification Status by {{ ucfirst($viewBy) }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ ucfirst($viewBy) }} Name</th>
                        <th class="px-4 py-3 text-center">Total Candidates</th>
                        <th class="px-4 py-3 text-center">With Complete Docs</th>
                        <th class="px-4 py-3 text-center">With Verified Docs</th>
                        <th class="px-4 py-3 text-center">Completion Rate</th>
                        <th class="px-4 py-3 text-center">Verification Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($data as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-center">{{ $item->total_candidates }}</td>
                        <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $item->complete_docs }}</td>
                        <td class="px-4 py-3 text-center text-blue-600 font-semibold">{{ $item->verified_docs }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 rounded-full h-2" style="width: {{ $item->completion_rate }}%"></div>
                                </div>
                                <span class="badge badge-{{ $item->completion_rate >= 80 ? 'success' : ($item->completion_rate >= 50 ? 'warning' : 'danger') }}">
                                    {{ $item->completion_rate }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 rounded-full h-2" style="width: {{ $item->verification_rate }}%"></div>
                                </div>
                                <span class="badge badge-{{ $item->verification_rate >= 80 ? 'success' : ($item->verification_rate >= 50 ? 'warning' : 'danger') }}">
                                    {{ $item->verification_rate }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
