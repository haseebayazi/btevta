@extends('layouts.app')
@section('title', 'Non-Compliant Departures')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-red-700">Non-Compliant Departures</h1>
            <p class="text-gray-600 mt-1">{{ $nonCompliantCount }} candidates require immediate attention</p>
        </div>
    </div>

    <!-- Non-Compliance Categories -->
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-red-50 border-red-300">
            <h3 class="font-semibold text-red-800 mb-2">Overdue Stages</h3>
            <p class="text-3xl font-bold text-red-900">{{ $overdueStagesCount }}</p>
        </div>
        <div class="card bg-orange-50 border-orange-300">
            <h3 class="font-semibold text-orange-800 mb-2">Missing Documents</h3>
            <p class="text-3xl font-bold text-orange-900">{{ $missingDocsCount }}</p>
        </div>
        <div class="card bg-yellow-50 border-yellow-300">
            <h3 class="font-semibold text-yellow-800 mb-2">Process Delays</h3>
            <p class="text-3xl font-bold text-yellow-900">{{ $delaysCount }}</p>
        </div>
    </div>

    <!-- Non-Compliant List -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Candidate</th>
                        <th class="px-4 py-3 text-center">Issues</th>
                        <th class="px-4 py-3 text-center">Severity</th>
                        <th class="px-4 py-3 text-center">Days Overdue</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($nonCompliantCases as $case)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium">{{ $case->candidate->name }}</p>
                                <p class="text-sm text-gray-600">{{ $case->candidate->passport_number }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-danger">{{ $case->issues_count }} issues</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $case->severity_color }}">
                                {{ ucfirst($case->severity) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-red-600">
                            {{ $case->days_overdue }} days
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-warning">{{ $case->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('departure.show', $case) }}" class="btn btn-sm btn-primary">
                                Resolve
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                            <p class="text-lg">All departures are compliant!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $nonCompliantCases->links() }}
        </div>
    </div>
</div>
@endsection