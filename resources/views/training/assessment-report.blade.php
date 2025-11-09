@extends('layouts.app')
@section('title', 'Assessment Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Assessment Report</h1>
        <div class="flex gap-2">
            <button onclick="exportToExcel()" class="btn btn-success">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print mr-2"></i>Print
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-5 gap-4">
            <div>
                <label class="form-label">Training Batch</label>
                <select name="training_id" class="form-input">
                    <option value="">All Batches</option>
                    @foreach($trainings as $t)
                        <option value="{{ $t->id }}">{{ $t->batch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Assessment Type</label>
                <select name="type" class="form-input">
                    <option value="">All Types</option>
                    <option value="theory">Theory</option>
                    <option value="practical">Practical</option>
                    <option value="final">Final</option>
                </select>
            </div>
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-input">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-input">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-blue-50">
            <div class="text-blue-800">
                <p class="text-sm font-medium">Total Assessments</p>
                <p class="text-3xl font-bold mt-1">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="card bg-green-50">
            <div class="text-green-800">
                <p class="text-sm font-medium">Average Score</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['avg_score'], 1) }}%</p>
            </div>
        </div>
        <div class="card bg-purple-50">
            <div class="text-purple-800">
                <p class="text-sm font-medium">Pass Rate</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['pass_rate'], 1) }}%</p>
            </div>
        </div>
        <div class="card bg-orange-50">
            <div class="text-orange-800">
                <p class="text-sm font-medium">Fail Rate</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['fail_rate'], 1) }}%</p>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full" id="assessmentTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Candidate</th>
                        <th class="px-4 py-3 text-left">Batch</th>
                        <th class="px-4 py-3 text-center">Type</th>
                        <th class="px-4 py-3 text-center">Marks</th>
                        <th class="px-4 py-3 text-center">Percentage</th>
                        <th class="px-4 py-3 text-center">Grade</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($assessments as $assessment)
                    <tr>
                        <td class="px-4 py-3">{{ $assessment->date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $assessment->candidate->name }}</td>
                        <td class="px-4 py-3">{{ $assessment->training->batch_name }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-secondary">{{ ucfirst($assessment->type) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">
                            {{ $assessment->marks_obtained }}/{{ $assessment->max_marks }}
                        </td>
                        <td class="px-4 py-3 text-center">{{ number_format($assessment->percentage, 1) }}%</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $assessment->grade_color }}">{{ $assessment->grade }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $assessment->passed ? 'success' : 'danger' }}">
                                {{ $assessment->passed ? 'Pass' : 'Fail' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No assessments found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $assessments->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportToExcel() {
    // Export logic here
    alert('Export functionality will be implemented');
}
</script>
@endpush
@endsection