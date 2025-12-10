@extends('layouts.app')
@section('title', 'Edit Campus')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Edit Campus</h2>
            <p class="text-gray-600 mt-1">Update information for {{ $campus->name }}</p>
        </div>
        <a href="{{ route('admin.campuses.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <form action="{{ route('admin.campuses.update', $campus->id) }}" method="POST" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        @csrf @method('PUT')

        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-building mr-2 text-blue-600"></i>
                Campus Information
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Campus Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Campus Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           value="{{ old('name', $campus->name) }}" placeholder="e.g., Lahore Campus">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                        Location <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="location" id="location" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('location') border-red-500 @enderror"
                           value="{{ old('location', $campus->location) }}" placeholder="e.g., Gulberg, Lahore">
                    @error('location')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Province -->
                <div>
                    <label for="province" class="block text-sm font-medium text-gray-700 mb-2">
                        Province <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="province" id="province" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('province') border-red-500 @enderror"
                           value="{{ old('province', $campus->province) }}" placeholder="e.g., Punjab">
                    @error('province')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- District -->
                <div>
                    <label for="district" class="block text-sm font-medium text-gray-700 mb-2">
                        District <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="district" id="district" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('district') border-red-500 @enderror"
                           value="{{ old('district', $campus->district) }}" placeholder="e.g., Lahore">
                    @error('district')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Contact Person -->
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-2">
                        Contact Person <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="contact_person" id="contact_person" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('contact_person') border-red-500 @enderror"
                           value="{{ old('contact_person', $campus->contact_person) }}" placeholder="Name of campus coordinator">
                    @error('contact_person')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="phone" id="phone" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror"
                           value="{{ old('phone', $campus->phone) }}" placeholder="e.g., +92 300 1234567">
                    @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-600">*</span>
                    </label>
                    <input type="email" name="email" id="email" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror"
                           value="{{ old('email', $campus->email) }}" placeholder="campus@btevta.gov.pk">
                    @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Address
                    </label>
                    <textarea name="address" id="address" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Complete campus address...">{{ old('address', $campus->address) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <a href="{{ route('admin.campuses.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>Update Campus
            </button>
        </div>
    </form>
</div>
@endsection
