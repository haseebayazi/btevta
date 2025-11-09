@extends('layouts.app')
@section('title', 'Attendance Report')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Attendance Report</h1>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print mr-2"></i>Print
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Batch</label>
                <select name="training_id" class="form-input">
                    <option value="">All Batches</option>
                    @foreach($trainings as $t)
                        <option value="{{ $t->id }}" {{ request('training_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->batch_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Batch</th>
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-center">Present</th>
                        <th class="px-4 py-3 text-center">Absent</th>
                        <th class="px-4 py-3 text-center">Late</th>
                        <th class="px-4 py-3 text-center">Attendance %</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($attendanceRecords as $record)
                    <tr>
                        <td class="px-4 py-3">{{ $record->date->format('M d, Y') }}</td>
                        <td class="px-4 py-3">{{ $record->training->batch_name }}</td>
                        <td class="px-4 py-3 text-center">{{ $record->total }}</td>
                        <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $record->present }}</td>
                        <td class="px-4 py-3 text-center text-red-600 font-semibold">{{ $record->absent }}</td>
                        <td class="px-4 py-3 text-center text-yellow-600 font-semibold">{{ $record->late }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge badge-{{ $record->percentage >= 80 ? 'success' : 'warning' }}">
                                {{ number_format($record->percentage, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $attendanceRecords->links() }}
        </div>
    </div>
</div>
@endsection