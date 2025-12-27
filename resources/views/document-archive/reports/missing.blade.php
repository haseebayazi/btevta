@extends('layouts.app')
@section('title', 'Missing Documents Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Missing Documents Summary</h1>
        <a href="{{ route('document-archive.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Documents
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="card bg-red-50">
            <p class="text-sm text-red-800">Candidates with Missing Docs</p>
            <p class="text-3xl font-bold text-red-900">{{ $stats['total_candidates_with_missing'] }}</p>
        </div>
        @foreach(['cnic' => 'CNIC', 'education_certificate' => 'Education', 'passport' => 'Passport', 'medical_certificate' => 'Medical'] as $key => $label)
        <div class="card bg-yellow-50">
            <p class="text-sm text-yellow-800">Missing {{ $label }}</p>
            <p class="text-3xl font-bold text-yellow-900">{{ $stats['missing_counts'][$key] ?? 0 }}</p>
        </div>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Campus</label>
                <select name="campus_id" class="form-input">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $id => $name)
                        <option value="{{ $id }}" {{ ($validated['campus_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ ($validated['status'] ?? '') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Filter</button>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">BTEVTA ID</th>
                        <th class="px-4 py-3 text-left">Candidate Name</th>
                        <th class="px-4 py-3 text-left">Campus</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Completion</th>
                        <th class="px-4 py-3 text-left">Missing Documents</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($candidatesWithMissing as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item['candidate']->btevta_id ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $item['candidate']->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $item['candidate']->campus->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $item['candidate']->status)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                    <div class="bg-{{ $item['completion_percentage'] >= 75 ? 'green' : ($item['completion_percentage'] >= 50 ? 'yellow' : 'red') }}-600 rounded-full h-2"
                                         style="width: {{ $item['completion_percentage'] }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ $item['completion_percentage'] }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($item['missing'] as $doc)
                                    <span class="badge badge-danger text-xs">{{ ucfirst(str_replace('_', ' ', $doc)) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('registration.show', $item['candidate']) }}" class="btn btn-sm btn-primary">
                                Upload Docs
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">All candidates have complete documents!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
