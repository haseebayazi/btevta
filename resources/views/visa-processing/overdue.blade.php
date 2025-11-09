@extends('layouts.app')
@section('title', 'Overdue Visa Processes')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Overdue Visa Processes</h1>
            <p class="text-gray-600 mt-1">{{ $overdueCount }} processes are overdue</p>
        </div>
        <button onclick="exportToExcel()" class="btn btn-success">
            <i class="fas fa-file-excel mr-2"></i>Export
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Campus</label>
                <select name="campus_id" class="form-input">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Stage</label>
                <select name="stage" class="form-input">
                    <option value="">All Stages</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}">Stage {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label">Days Overdue</label>
                <select name="days" class="form-input">
                    <option value="">All</option>
                    <option value="7">7+ days</option>
                    <option value="14">14+ days</option>
                    <option value="30">30+ days</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Overdue List -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Candidate</th>
                        <th class="px-4 py-3 text-left">Passport</th>
                        <th class="px-4 py-3 text-center">Current Stage</th>
                        <th class="px-4 py-3 text-center">Days Overdue</th>
                        <th class="px-4 py-3 text-center">Expected Date</th>
                        <th class="px-4 py-3 text-center">Campus</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($overdueProcesses as $process)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('visa-processing.show', $process) }}" class="text-blue-600 hover:text-blue-700">
                                {{ $process->candidate->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $process->candidate->passport_number }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-secondary">Stage {{ $process->current_stage }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $process->days_overdue > 30 ? 'danger' : ($process->days_overdue > 14 ? 'warning' : 'secondary') }}">
                                {{ $process->days_overdue }} days
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm">
                            {{ $process->expected_completion_date->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm">{{ $process->campus->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('visa-processing.show', $process) }}" 
                               class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                            <p>No overdue processes!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $overdueProcesses->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportToExcel() {
    alert('Export functionality will be implemented');
}
</script>
@endpush
@endsection