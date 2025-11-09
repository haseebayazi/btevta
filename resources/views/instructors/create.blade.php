@extends('layouts.app')
@section('title', 'Add New Instructor')
@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Add New Instructor</h1>
        <p class="text-gray-600 mt-1">Fill in the details to create a new instructor record</p>
    </div>

    <div class="card">
        <form action="{{ route('instructors.store') }}" method="POST">
            @csrf

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="form-input w-full @error('name') border-red-500 @enderror" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- CNIC -->
                <div>
                    <label for="cnic" class="form-label">CNIC <span class="text-red-500">*</span></label>
                    <input type="text" id="cnic" name="cnic" value="{{ old('cnic') }}"
                           placeholder="xxxxx-xxxxxxx-x"
                           class="form-input w-full @error('cnic') border-red-500 @enderror" required>
                    @error('cnic')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-input w-full @error('email') border-red-500 @enderror" required>
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="form-label">Phone <span class="text-red-500">*</span></label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="form-input w-full @error('phone') border-red-500 @enderror" required>
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Qualification -->
                <div>
                    <label for="qualification" class="form-label">Qualification</label>
                    <input type="text" id="qualification" name="qualification" value="{{ old('qualification') }}"
                           class="form-input w-full">
                </div>

                <!-- Specialization -->
                <div>
                    <label for="specialization" class="form-label">Specialization</label>
                    <input type="text" id="specialization" name="specialization" value="{{ old('specialization') }}"
                           class="form-input w-full">
                </div>

                <!-- Experience Years -->
                <div>
                    <label for="experience_years" class="form-label">Experience (Years)</label>
                    <input type="number" id="experience_years" name="experience_years" value="{{ old('experience_years', 0) }}"
                           min="0" class="form-input w-full">
                </div>

                <!-- Campus -->
                <div>
                    <label for="campus_id" class="form-label">Campus</label>
                    <select id="campus_id" name="campus_id" class="form-select w-full">
                        <option value="">Select Campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Trade -->
                <div>
                    <label for="trade_id" class="form-label">Trade</label>
                    <select id="trade_id" name="trade_id" class="form-select w-full">
                        <option value="">Select Trade</option>
                        @foreach($trades as $trade)
                            <option value="{{ $trade->id }}" {{ old('trade_id') == $trade->id ? 'selected' : '' }}>
                                {{ $trade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Employment Type -->
                <div>
                    <label for="employment_type" class="form-label">Employment Type <span class="text-red-500">*</span></label>
                    <select id="employment_type" name="employment_type" class="form-select w-full" required>
                        <option value="permanent" {{ old('employment_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="visiting" {{ old('employment_type') == 'visiting' ? 'selected' : '' }}>Visiting</option>
                    </select>
                </div>

                <!-- Joining Date -->
                <div>
                    <label for="joining_date" class="form-label">Joining Date</label>
                    <input type="date" id="joining_date" name="joining_date" value="{{ old('joining_date') }}"
                           class="form-input w-full">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" class="form-select w-full" required>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="on_leave" {{ old('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                        <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" rows="3"
                              class="form-input w-full">{{ old('address') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Create Instructor
                </button>
                <a href="{{ route('instructors.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
