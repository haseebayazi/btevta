@extends('layouts.app')
@section('title', 'Add Instructor')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('instructors.index') }}"
           class="text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Add Instructor</h2>
            <p class="text-gray-500 text-sm mt-0.5">Create a new instructor record</p>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex gap-2 mb-2">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <p class="text-sm font-medium text-red-700">Please fix the following errors:</p>
            </div>
            <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5 ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('instructors.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Personal Information --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-user mr-2 text-blue-500"></i>Personal Information
                </h3>
            </div>
            <div class="p-6 grid md:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cnic" class="block text-sm font-medium text-gray-700 mb-1">
                        CNIC <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="cnic" name="cnic" value="{{ old('cnic') }}"
                           placeholder="xxxxx-xxxxxxx-x" required
                           class="w-full px-3 py-2 border rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  {{ $errors->has('cnic') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                    @error('cnic')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Phone <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea id="address" name="address" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">{{ old('address') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Professional Details --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-briefcase mr-2 text-green-500"></i>Professional Details
                </h3>
            </div>
            <div class="p-6 grid md:grid-cols-2 gap-5">
                <div>
                    <label for="campus_id" class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                    <select id="campus_id" name="campus_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">— Select Campus —</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="trade_id" class="block text-sm font-medium text-gray-700 mb-1">Trade</label>
                    <select id="trade_id" name="trade_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">— Select Trade —</option>
                        @foreach($trades as $trade)
                            <option value="{{ $trade->id }}" {{ old('trade_id') == $trade->id ? 'selected' : '' }}>
                                {{ $trade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="qualification" class="block text-sm font-medium text-gray-700 mb-1">Qualification</label>
                    <input type="text" id="qualification" name="qualification" value="{{ old('qualification') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                    <input type="text" id="specialization" name="specialization" value="{{ old('specialization') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="experience_years" class="block text-sm font-medium text-gray-700 mb-1">Experience (Years)</label>
                    <input type="number" id="experience_years" name="experience_years"
                           value="{{ old('experience_years', 0) }}" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Employment Type <span class="text-red-500">*</span>
                    </label>
                    <select id="employment_type" name="employment_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="permanent" {{ old('employment_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract"  {{ old('employment_type') == 'contract'  ? 'selected' : '' }}>Contract</option>
                        <option value="visiting"  {{ old('employment_type') == 'visiting'  ? 'selected' : '' }}>Visiting</option>
                    </select>
                </div>

                <div>
                    <label for="joining_date" class="block text-sm font-medium text-gray-700 mb-1">Joining Date</label>
                    <input type="date" id="joining_date" name="joining_date" value="{{ old('joining_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="active"     {{ old('status', 'active') == 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="inactive"   {{ old('status') == 'inactive'   ? 'selected' : '' }}>Inactive</option>
                        <option value="on_leave"   {{ old('status') == 'on_leave'   ? 'selected' : '' }}>On Leave</option>
                        <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-save mr-1"></i> Create Instructor
            </button>
            <a href="{{ route('instructors.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
