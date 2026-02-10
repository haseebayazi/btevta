@extends('layouts.app')

@section('title', 'Add New Candidate')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Add New Candidate</h1>
                <p class="text-gray-600 mt-2">Register a new candidate in the system</p>
            </div>
            <a href="{{ route('candidates.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Back to Candidates
            </a>
        </div>
    </div>

    <!-- Error Summary -->
    @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-start">
            <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
            <div>
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="list-disc list-inside mt-2 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-8">
        <form action="{{ route('candidates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- Personal Information Section -->
            <div class="border-b pb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Personal Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror" 
                               value="{{ old('name') }}" required>
                        @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Father Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Father's Name *</label>
                        <input type="text" name="father_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('father_name') border-red-500 @enderror"
                               value="{{ old('father_name') }}" required>
                        @error('father_name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- CNIC -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CNIC *</label>
                        <input type="text" name="cnic" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cnic') border-red-500 @enderror"
                               value="{{ old('cnic') }}" placeholder="12345-1234567-1" required>
                        @error('cnic')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                        <input type="date" name="date_of_birth" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('date_of_birth') border-red-500 @enderror"
                               value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                        <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gender') border-red-500 @enderror" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Photo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                        <input type="file" name="photo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('photo') border-red-500 @enderror"
                               accept="image/*">
                        @error('photo')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="border-b pb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Contact Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                               value="{{ old('email') }}">
                        @error('email')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                        <input type="tel" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror"
                               value="{{ old('phone') }}" placeholder="03XX-XXXXXXX" required>
                        @error('phone')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                        @error('address')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- District -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">District *</label>
                        <input type="text" name="district" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('district') border-red-500 @enderror"
                               value="{{ old('district') }}" required>
                        @error('district')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Tehsil -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tehsil</label>
                        <input type="text" name="tehsil" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('tehsil') border-red-500 @enderror"
                               value="{{ old('tehsil') }}">
                        @error('tehsil')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <!-- Training Information Section -->
            <div class="border-b pb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Training Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Campus -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campus (Optional)</label>
                        <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('campus_id') border-red-500 @enderror">
                            <option value="">Select Campus (can be assigned later)</option>
                            @foreach($campuses ?? [] as $campus)
                                <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Campus can be assigned later from candidate profile</p>
                        @error('campus_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Trade -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trade *</label>
                        <select name="trade_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('trade_id') border-red-500 @enderror" required>
                            <option value="">Select Trade</option>
                            @foreach($trades ?? [] as $trade)
                                <option value="{{ $trade->id }}" {{ old('trade_id') == $trade->id ? 'selected' : '' }}>{{ $trade->name }}</option>
                            @endforeach
                        </select>
                        @error('trade_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- OEP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Overseas Employment Programme</label>
                        <select name="oep_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('oep_id') border-red-500 @enderror">
                            <option value="">Select OEP</option>
                            @foreach($oeps ?? [] as $oep)
                                <option value="{{ $oep->id }}" {{ old('oep_id') == $oep->id ? 'selected' : '' }}>{{ $oep->name }}</option>
                            @endforeach
                        </select>
                        @error('oep_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <!-- Remarks Section -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <textarea name="remarks" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('remarks') border-red-500 @enderror">{{ old('remarks') }}</textarea>
                @error('remarks')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-8 border-t">
                <a href="{{ route('candidates.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Create Candidate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection