@extends('layouts.app')

@section('title', 'Mark Attendance - ' . $training->title)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Mark Attendance</h1>
            <p class="text-gray-600 mt-1">{{ $training->title }} - {{ $training->batch_name }}</p>
        </div>
        <a href="{{ route('training.show', $training) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Training
        </a>
    </div>

    <!-- Attendance Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form id="attendanceForm" method="POST" action="{{ route('training.attendance.store', $training) }}">
            @csrf
            
            <!-- Date and Session Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="attendance_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="attendance_date" 
                           name="attendance_date" 
                           value="{{ old('attendance_date', date('Y-m-d')) }}"
                           max="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                    @error('attendance_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="session_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Session Type
                    </label>
                    <select id="session_type" 
                            name="session_type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="full_day" {{ old('session_type') == 'full_day' ? 'selected' : '' }}>Full Day</option>
                        <option value="morning" {{ old('session_type') == 'morning' ? 'selected' : '' }}>Morning Session</option>
                        <option value="afternoon" {{ old('session_type') == 'afternoon' ? 'selected' : '' }}>Afternoon Session</option>
                    </select>
                </div>

                <div>
                    <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Instructor
                    </label>
                    <select id="instructor_id" 
                            name="instructor_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Instructor</option>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" {{ old('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <div class="flex flex-wrap gap-2">
                    <button type="button" 
                            onclick="markAll('present')" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-check-double mr-1"></i>Mark All Present
                    </button>
                    <button type="button" 
                            onclick="markAll('absent')" 
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-times-circle mr-1"></i>Mark All Absent
                    </button>
                    <button type="button" 
                            onclick="clearAll()" 
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-eraser mr-1"></i>Clear All
                    </button>
                </div>
                <div class="text-sm text-gray-600">
                    Total: <span class="font-bold text-gray-800">{{ $candidates->count() }}</span> candidates
                </div>
            </div>

            <!-- Candidates List -->
            <div class="space-y-2 mb-6">
                @forelse($candidates as $candidate)
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex items-center space-x-4 flex-1">
                            <img src="{{ $candidate->profile_photo_url ?? asset('images/default-avatar.png') }}" 
                                 alt="{{ $candidate->name }}"
                                 class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $candidate->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $candidate->passport_number }}</p>
                            </div>
                        </div>

                        <!-- Attendance Options -->
                        <div class="flex items-center space-x-3">
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" 
                                       name="attendance[{{ $candidate->id }}]" 
                                       value="present"
                                       class="attendance-radio w-5 h-5 text-green-600 focus:ring-green-500 cursor-pointer">
                                <span class="ml-2 text-sm font-medium text-green-700 group-hover:text-green-800">Present</span>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" 
                                       name="attendance[{{ $candidate->id }}]" 
                                       value="absent"
                                       class="attendance-radio w-5 h-5 text-red-600 focus:ring-red-500 cursor-pointer">
                                <span class="ml-2 text-sm font-medium text-red-700 group-hover:text-red-800">Absent</span>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" 
                                       name="attendance[{{ $candidate->id }}]" 
                                       value="late"
                                       class="attendance-radio w-5 h-5 text-yellow-600 focus:ring-yellow-500 cursor-pointer">
                                <span class="ml-2 text-sm font-medium text-yellow-700 group-hover:text-yellow-800">Late</span>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <input type="radio" 
                                       name="attendance[{{ $candidate->id }}]" 
                                       value="excused"
                                       class="attendance-radio w-5 h-5 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span class="ml-2 text-sm font-medium text-blue-700 group-hover:text-blue-800">Excused</span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-users text-5xl mb-3 text-gray-400"></i>
                        <p class="text-lg font-medium">No candidates enrolled in this training batch.</p>
                        <a href="{{ route('training.edit', $training) }}" class="text-blue-600 hover:text-blue-700 mt-2 inline-block">
                            Add Candidates
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- Notes Section -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Session Notes (Optional)
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="3"
                          placeholder="Add notes about today's session, topics covered, or any issues..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('training.show', $training) }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
                    <i class="fas fa-save mr-2"></i>Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Mark all candidates with specific status
    function markAll(status) {
        document.querySelectorAll('.attendance-radio').forEach(radio => {
            if (radio.value === status) {
                radio.checked = true;
            }
        });
    }

    // Clear all attendance selections
    function clearAll() {
        document.querySelectorAll('.attendance-radio').forEach(radio => {
            radio.checked = false;
        });
    }

    // Form validation before submit
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        const checkedRadios = document.querySelectorAll('.attendance-radio:checked');
        
        if (checkedRadios.length === 0) {
            e.preventDefault();
            alert('Please mark attendance for at least one candidate before submitting.');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    });
</script>
@endpush
@endsection
