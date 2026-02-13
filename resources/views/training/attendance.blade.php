@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('training.index') }}" class="hover:text-gray-700">Training</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Mark Attendance</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Mark Attendance</h2>
            <p class="text-gray-500 text-sm mt-1">Record daily attendance for training batches</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm inline-flex items-center">
                <i class="fas fa-arrow-left mr-1"></i> Back to Training
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
        <div>
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
        <button type="button" class="text-green-600 hover:text-green-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
        <div>
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    {{-- Batch Selection --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-5 py-3 border-b">
            <h5 class="font-semibold text-gray-800"><i class="fas fa-filter mr-2"></i>Select Batch & Date</h5>
        </div>
        <div class="p-5">
            <form action="{{ route('training.attendance-form') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label class="block font-bold text-sm text-gray-700 mb-1">Select Batch</label>
                    <select name="batch_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" onchange="this.form.submit()">
                        <option value="">-- Select a Batch --</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }} ({{ $batch->batch_code }}) - {{ $batch->candidates->count() }} candidates
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-4">
                    <label class="block font-bold text-sm text-gray-700 mb-1">Attendance Date</label>
                    <input type="date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" value="{{ $date }}" max="{{ date('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-sync-alt"></i> Load
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Attendance Form --}}
    @if($selectedBatch)
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="bg-blue-600 text-white px-5 py-3">
                <div class="flex justify-between items-center">
                    <div>
                        <h5 class="font-semibold"><i class="fas fa-clipboard-check mr-2"></i>{{ $selectedBatch->name }}</h5>
                        <span class="text-sm text-blue-100">Date: {{ \Carbon\Carbon::parse($date)->format('d M Y, l') }}</span>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white text-gray-800">{{ $selectedBatch->candidates->count() }} Candidates</span>
                    </div>
                </div>
            </div>
            <div class="p-5">
                @if($selectedBatch->candidates->count() > 0)
                    <form action="{{ route('training.bulk-attendance') }}" method="POST" id="attendanceForm">
                        @csrf
                        <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                        <input type="hidden" name="date" value="{{ $date }}">

                        {{-- Quick Actions --}}
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 pb-3 border-b">
                            <div class="flex space-x-2 mb-3 sm:mb-0">
                                <button type="button" class="border border-green-500 text-green-600 hover:bg-green-50 px-3 py-1.5 rounded-lg text-sm" onclick="markAll('present')">
                                    <i class="fas fa-check-double"></i> All Present
                                </button>
                                <button type="button" class="border border-red-500 text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm" onclick="markAll('absent')">
                                    <i class="fas fa-times-circle"></i> All Absent
                                </button>
                                <button type="button" class="border border-gray-400 text-gray-600 hover:bg-gray-50 px-3 py-1.5 rounded-lg text-sm" onclick="clearAll()">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                            </div>
                            <div class="text-gray-500 text-sm">
                                Total: <strong id="totalCount">0</strong> |
                                Present: <strong id="presentCount" class="text-green-600">0</strong> |
                                Absent: <strong id="absentCount" class="text-red-600">0</strong> |
                                Leave: <strong id="leaveCount" class="text-yellow-600">0</strong>
                            </div>
                        </div>

                        {{-- Candidates List --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600" style="width: 5%">#</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600" style="width: 15%">TheLeap ID</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600" style="width: 25%">Name</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600" style="width: 30%">Attendance Status</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-600" style="width: 25%">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($selectedBatch->candidates as $index => $candidate)
                                        @php
                                            $existingAttendance = $candidate->attendances->first();
                                            $currentStatus = $existingAttendance ? $existingAttendance->status : null;
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ $candidate->btevta_id }}</td>
                                            <td class="px-4 py-3">
                                                <span class="font-medium text-gray-800">{{ $candidate->name }}</span>
                                                <span class="block text-xs text-gray-500">{{ $candidate->trade->name ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div x-data="{ status: '{{ $currentStatus }}' }" class="flex space-x-1">
                                                    <label :class="status === 'present' ? 'bg-green-500 text-white border-green-500' : 'border-green-500 text-green-600 hover:bg-green-50'" class="cursor-pointer px-3 py-1.5 rounded-lg text-sm transition-colors border inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $index }}][status]" value="present" x-model="status" class="sr-only status-radio" data-candidate="{{ $index }}">
                                                        <i class="fas fa-check mr-1"></i> Present
                                                    </label>
                                                    <label :class="status === 'absent' ? 'bg-red-500 text-white border-red-500' : 'border-red-500 text-red-600 hover:bg-red-50'" class="cursor-pointer px-3 py-1.5 rounded-lg text-sm transition-colors border inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $index }}][status]" value="absent" x-model="status" class="sr-only status-radio" data-candidate="{{ $index }}">
                                                        <i class="fas fa-times mr-1"></i> Absent
                                                    </label>
                                                    <label :class="status === 'leave' ? 'bg-yellow-500 text-white border-yellow-500' : 'border-yellow-500 text-yellow-600 hover:bg-yellow-50'" class="cursor-pointer px-3 py-1.5 rounded-lg text-sm transition-colors border inline-flex items-center">
                                                        <input type="radio" name="attendances[{{ $index }}][status]" value="leave" x-model="status" class="sr-only status-radio" data-candidate="{{ $index }}">
                                                        <i class="fas fa-clock mr-1"></i> Leave
                                                    </label>
                                                </div>
                                                <input type="hidden" name="attendances[{{ $index }}][candidate_id]" value="{{ $candidate->id }}">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" name="attendances[{{ $index }}][remarks]" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       placeholder="Optional remarks" value="{{ $existingAttendance->remarks ?? '' }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Submit --}}
                        <div class="flex justify-between pt-4 border-t">
                            <a href="{{ route('training.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm inline-flex items-center">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium">
                                <i class="fas fa-save mr-1"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                        <i class="fas fa-exclamation-triangle mr-2"></i>No candidates enrolled in this batch.
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-5 text-center py-12">
                <i class="fas fa-users fa-4x text-gray-300 mb-3"></i>
                <h4 class="text-gray-500 text-lg font-semibold">Select a Batch</h4>
                <p class="text-gray-500 text-sm mt-1">Please select a batch from the dropdown above to mark attendance.</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Mark all candidates with a specific status
    function markAll(status) {
        document.querySelectorAll('.status-radio').forEach(radio => {
            if (radio.value === status) {
                radio.checked = true;
                // Dispatch input event so Alpine.js picks up the change
                radio.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        updateCounts();
    }

    // Clear all selections
    function clearAll() {
        document.querySelectorAll('.status-radio').forEach(radio => {
            radio.checked = false;
        });
        // Reset all Alpine.js x-data status values
        document.querySelectorAll('[x-data]').forEach(el => {
            if (el.__x) {
                el.__x.$data.status = '';
            } else if (el._x_dataStack) {
                el._x_dataStack[0].status = '';
            }
        });
        updateCounts();
    }

    // Update attendance counts
    function updateCounts() {
        const total = document.querySelectorAll('input[type="hidden"][name$="[candidate_id]"]').length;
        const present = document.querySelectorAll('.status-radio[value="present"]:checked').length;
        const absent = document.querySelectorAll('.status-radio[value="absent"]:checked').length;
        const leave = document.querySelectorAll('.status-radio[value="leave"]:checked').length;

        document.getElementById('totalCount').textContent = total;
        document.getElementById('presentCount').textContent = present;
        document.getElementById('absentCount').textContent = absent;
        document.getElementById('leaveCount').textContent = leave;
    }

    // Update counts on radio change
    document.querySelectorAll('.status-radio').forEach(radio => {
        radio.addEventListener('change', updateCounts);
    });

    // Initial count
    document.addEventListener('DOMContentLoaded', updateCounts);

    // Form validation
    document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
        const checkedRadios = document.querySelectorAll('.status-radio:checked');
        if (checkedRadios.length === 0) {
            e.preventDefault();
            alert('Please mark attendance for at least one candidate.');
            return false;
        }
    });
</script>
@endpush
@endsection
