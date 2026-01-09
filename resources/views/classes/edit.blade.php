@extends('layouts.app')

@section('title', 'Edit Training Class')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Edit Training Class</h1>
        <p class="text-gray-600 mt-1">Update class details for: {{ $class->class_name }}</p>
    </div>

    <div class="card">
        <form action="{{ route('classes.update', $class) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Class Name -->
                <div>
                    <label for="class_name" class="form-label">Class Name <span class="text-red-500">*</span></label>
                    <input type="text" id="class_name" name="class_name" value="{{ old('class_name', $class->class_name) }}"
                           class="form-input w-full @error('class_name') border-red-500 @enderror" required>
                    @error('class_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Class Code -->
                <div>
                    <label for="class_code" class="form-label">Class Code</label>
                    <input type="text" id="class_code" name="class_code" value="{{ old('class_code', $class->class_code) }}"
                           class="form-input w-full @error('class_code') border-red-500 @enderror">
                    @error('class_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Campus -->
                <div>
                    <label for="campus_id" class="form-label">Campus</label>
                    <select id="campus_id" name="campus_id" class="form-select w-full @error('campus_id') border-red-500 @enderror">
                        <option value="">Select Campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id', $class->campus_id) == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('campus_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Trade -->
                <div>
                    <label for="trade_id" class="form-label">Trade</label>
                    <select id="trade_id" name="trade_id" class="form-select w-full @error('trade_id') border-red-500 @enderror">
                        <option value="">Select Trade</option>
                        @foreach($trades as $trade)
                            <option value="{{ $trade->id }}" {{ old('trade_id', $class->trade_id) == $trade->id ? 'selected' : '' }}>
                                {{ $trade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('trade_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Instructor -->
                <div>
                    <label for="instructor_id" class="form-label">Instructor</label>
                    <select id="instructor_id" name="instructor_id" class="form-select w-full @error('instructor_id') border-red-500 @enderror">
                        <option value="">Select Instructor</option>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" {{ old('instructor_id', $class->instructor_id) == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->name }} ({{ $instructor->specialization ?? 'General' }})
                            </option>
                        @endforeach
                    </select>
                    @error('instructor_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batch -->
                <div>
                    <label for="batch_id" class="form-label">Batch</label>
                    <select id="batch_id" name="batch_id" class="form-select w-full @error('batch_id') border-red-500 @enderror">
                        <option value="">Select Batch</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('batch_id', $class->batch_id) == $batch->id ? 'selected' : '' }}>
                                {{ $batch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('batch_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date"
                           value="{{ old('start_date', $class->start_date?->format('Y-m-d')) }}"
                           class="form-input w-full @error('start_date') border-red-500 @enderror">
                    @error('start_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date"
                           value="{{ old('end_date', $class->end_date?->format('Y-m-d')) }}"
                           class="form-input w-full @error('end_date') border-red-500 @enderror">
                    @error('end_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Capacity -->
                <div>
                    <label for="max_capacity" class="form-label">Maximum Capacity <span class="text-red-500">*</span></label>
                    <input type="number" id="max_capacity" name="max_capacity"
                           value="{{ old('max_capacity', $class->max_capacity) }}"
                           min="1" class="form-input w-full @error('max_capacity') border-red-500 @enderror" required>
                    @error('max_capacity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @if($class->current_enrollment > 0)
                        <p class="text-sm text-gray-500 mt-1">Current enrollment: {{ $class->current_enrollment }}</p>
                    @endif
                </div>

                <!-- Room Number -->
                <div>
                    <label for="room_number" class="form-label">Room Number</label>
                    <input type="text" id="room_number" name="room_number"
                           value="{{ old('room_number', $class->room_number) }}"
                           class="form-input w-full @error('room_number') border-red-500 @enderror">
                    @error('room_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" class="form-select w-full @error('status') border-red-500 @enderror" required>
                        <option value="scheduled" {{ old('status', $class->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="ongoing" {{ old('status', $class->status) == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ old('status', $class->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $class->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Schedule -->
                <div class="md:col-span-2">
                    <label for="schedule" class="form-label">Schedule</label>
                    <textarea id="schedule" name="schedule" rows="2"
                              class="form-input w-full @error('schedule') border-red-500 @enderror">{{ old('schedule', $class->schedule) }}</textarea>
                    @error('schedule')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Update Class
                </button>
                <a href="{{ route('classes.show', $class) }}" class="btn btn-info">
                    <i class="fas fa-eye mr-2"></i>View Class
                </a>
                <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
