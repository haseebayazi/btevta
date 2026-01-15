@extends('layouts.app')

@section('title', 'Edit Candidate')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Candidate</h1>
                <p class="text-gray-600 mt-2">Update candidate information: <strong>{{ $candidate->name }}</strong></p>
            </div>
            <a href="{{ route('candidates.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Back to Candidates
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-8">
        <form action="{{ route('candidates.update', $candidate) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Personal Information Section -->
            <div class="border-b pb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Personal Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{--
                        SECURITY: TheLeap ID and Application ID are immutable system identifiers.
                        These fields are disabled intentionally:
                        1. They are system-generated unique identifiers
                        2. Changing them would break referential integrity
                        3. They are used for audit trails and external system integrations
                        AUDIT FIX (P3): Documented disabled field security rationale
                    --}}
                    <!-- TheLeap ID (Immutable - System Generated) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">TheLeap ID</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100"
                               value="{{ $candidate->btevta_id }}" disabled
                               title="System-generated identifier - cannot be modified">
                    </div>

                    <!-- Application ID (Immutable - System Generated) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Application ID</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100"
                               value="{{ $candidate->application_id }}" disabled
                               title="System-generated identifier - cannot be modified">
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror" 
                               value="{{ old('name', $candidate->name) }}" required>
                        @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Father Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Father's Name *</label>
                        <input type="text" name="father_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('father_name') border-red-500 @enderror"
                               value="{{ old('father_name', $candidate->father_name) }}" required>
                        @error('father_name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- CNIC -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CNIC *</label>
                        <input type="text" name="cnic" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cnic') border-red-500 @enderror"
                               value="{{ old('cnic', $candidate->cnic) }}" placeholder="12345-1234567-1" required>
                        @error('cnic')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                        <input type="date" name="date_of_birth" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('date_of_birth') border-red-500 @enderror"
                               value="{{ old('date_of_birth', $candidate->date_of_birth?->format('Y-m-d')) }}" required>
                        @error('date_of_birth')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                        <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gender') border-red-500 @enderror" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $candidate->gender) == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $candidate->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Current Photo -->
                    @if($candidate->photo_path)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Photo</label>
                        <div class="relative">
                            <img src="{{ Storage::url($candidate->photo_path) }}" alt="{{ $candidate->name }}" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                        </div>
                    </div>
                    @endif

                    <!-- Photo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Change Photo</label>
                        <input type="file" name="photo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('photo') border-red-500 @enderror"
                               accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to keep current photo</p>
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
                               value="{{ old('email', $candidate->email) }}">
                        @error('email')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                        <input type="tel" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror"
                               value="{{ old('phone', $candidate->phone) }}" placeholder="03XX-XXXXXXX" required>
                        @error('phone')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror">{{ old('address', $candidate->address) }}</textarea>
                        @error('address')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- District -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">District *</label>
                        <input type="text" name="district" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('district') border-red-500 @enderror"
                               value="{{ old('district', $candidate->district) }}" required>
                        @error('district')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Tehsil -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tehsil</label>
                        <input type="text" name="tehsil" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('tehsil') border-red-500 @enderror"
                               value="{{ old('tehsil', $candidate->tehsil) }}">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campus *</label>
                        <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('campus_id') border-red-500 @enderror" required>
                            <option value="">Select Campus</option>
                            @foreach($campuses ?? [] as $campus)
                                <option value="{{ $campus->id }}" {{ old('campus_id', $candidate->campus_id) == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                            @endforeach
                        </select>
                        @error('campus_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Trade -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trade *</label>
                        <select name="trade_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('trade_id') border-red-500 @enderror" required>
                            <option value="">Select Trade</option>
                            @foreach($trades ?? [] as $trade)
                                <option value="{{ $trade->id }}" {{ old('trade_id', $candidate->trade_id) == $trade->id ? 'selected' : '' }}>{{ $trade->name }}</option>
                            @endforeach
                        </select>
                        @error('trade_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Batch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch</label>
                        <select name="batch_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('batch_id') border-red-500 @enderror">
                            <option value="">No Batch Assigned</option>
                            @foreach($batches ?? [] as $batch)
                                <option value="{{ $batch->id }}" {{ old('batch_id', $candidate->batch_id) == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->batch_code }} - {{ $batch->name ?? $batch->trade->name ?? 'N/A' }}
                                    @if($batch->status === 'planned') [Planned] @endif
                                </option>
                            @endforeach
                        </select>
                        @error('batch_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- OEP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Overseas Employment Programme</label>
                        <select name="oep_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('oep_id') border-red-500 @enderror">
                            <option value="">Select OEP</option>
                            @foreach($oeps ?? [] as $oep)
                                <option value="{{ $oep->id }}" {{ old('oep_id', $candidate->oep_id) == $oep->id ? 'selected' : '' }}>{{ $oep->name }}</option>
                            @endforeach
                        </select>
                        @error('oep_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror" required>
                            <option value="{{ \App\Models\Candidate::STATUS_NEW }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_NEW ? 'selected' : '' }}>New</option>
                            <option value="{{ \App\Models\Candidate::STATUS_SCREENING }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_SCREENING ? 'selected' : '' }}>Screening</option>
                            <option value="{{ \App\Models\Candidate::STATUS_REGISTERED }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_REGISTERED ? 'selected' : '' }}>Registered</option>
                            <option value="{{ \App\Models\Candidate::STATUS_TRAINING }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_TRAINING ? 'selected' : '' }}>Training</option>
                            <option value="{{ \App\Models\Candidate::STATUS_VISA_PROCESS }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_VISA_PROCESS ? 'selected' : '' }}>Visa Process</option>
                            <option value="{{ \App\Models\Candidate::STATUS_READY }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_READY ? 'selected' : '' }}>Ready</option>
                            <option value="{{ \App\Models\Candidate::STATUS_DEPARTED }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_DEPARTED ? 'selected' : '' }}>Departed</option>
                            <option value="{{ \App\Models\Candidate::STATUS_REJECTED }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_REJECTED ? 'selected' : '' }}>Rejected</option>
                            <option value="{{ \App\Models\Candidate::STATUS_DROPPED }}" {{ old('status', $candidate->status) == \App\Models\Candidate::STATUS_DROPPED ? 'selected' : '' }}>Dropped</option>
                        </select>
                        @error('status')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <!-- Remarks Section -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <textarea name="remarks" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('remarks') border-red-500 @enderror">{{ old('remarks', $candidate->remarks) }}</textarea>
                @error('remarks')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-8 border-t">
                <a href="{{ route('candidates.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Update Candidate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
