@extends('layouts.app')

@section('title', 'Add Equipment - ' . config('app.name'))

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Add New Equipment</h1>
            <p class="text-gray-600 mt-1">Register new equipment to campus inventory</p>
        </div>
        <a href="{{ route('equipment.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Equipment
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('equipment.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                </div>

                <div>
                    <label for="campus_id" class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                    <select name="campus_id" id="campus_id" class="form-select w-full @error('campus_id') border-red-500 @enderror" required>
                        <option value="">Select Campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('campus_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Equipment Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-input w-full @error('name') border-red-500 @enderror" required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category" id="category" class="form-select w-full @error('category') border-red-500 @enderror" required>
                        <option value="">Select Category</option>
                        @foreach(\App\Models\CampusEquipment::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 1) }}" min="1" class="form-input w-full @error('quantity') border-red-500 @enderror" required>
                    @error('quantity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3" class="form-textarea w-full @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Technical Details -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b mt-4">Technical Details</h3>
                </div>

                <div>
                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" id="brand" value="{{ old('brand') }}" class="form-input w-full @error('brand') border-red-500 @enderror">
                    @error('brand')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" id="model" value="{{ old('model') }}" class="form-input w-full @error('model') border-red-500 @enderror">
                    @error('model')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                    <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number') }}" class="form-input w-full @error('serial_number') border-red-500 @enderror">
                    @error('serial_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">Condition <span class="text-red-500">*</span></label>
                    <select name="condition" id="condition" class="form-select w-full @error('condition') border-red-500 @enderror" required>
                        @foreach(\App\Models\CampusEquipment::CONDITIONS as $key => $label)
                            <option value="{{ $key }}" {{ old('condition', 'good') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('condition')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" class="form-select w-full @error('status') border-red-500 @enderror" required>
                        @foreach(\App\Models\CampusEquipment::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ old('status', 'available') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Purchase & Financial Information -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b mt-4">Purchase & Financial Information</h3>
                </div>

                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                    <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}" class="form-input w-full @error('purchase_date') border-red-500 @enderror">
                    @error('purchase_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="purchase_cost" class="block text-sm font-medium text-gray-700 mb-1">Purchase Cost (PKR)</label>
                    <input type="number" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost') }}" step="0.01" min="0" class="form-input w-full @error('purchase_cost') border-red-500 @enderror">
                    @error('purchase_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="current_value" class="block text-sm font-medium text-gray-700 mb-1">Current Value (PKR)</label>
                    <input type="number" name="current_value" id="current_value" value="{{ old('current_value') }}" step="0.01" min="0" class="form-input w-full @error('current_value') border-red-500 @enderror">
                    @error('current_value')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Maintenance Schedule -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b mt-4">Maintenance Schedule</h3>
                </div>

                <div>
                    <label for="last_maintenance_date" class="block text-sm font-medium text-gray-700 mb-1">Last Maintenance Date</label>
                    <input type="date" name="last_maintenance_date" id="last_maintenance_date" value="{{ old('last_maintenance_date') }}" class="form-input w-full @error('last_maintenance_date') border-red-500 @enderror">
                    @error('last_maintenance_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="next_maintenance_date" class="block text-sm font-medium text-gray-700 mb-1">Next Maintenance Date</label>
                    <input type="date" name="next_maintenance_date" id="next_maintenance_date" value="{{ old('next_maintenance_date') }}" class="form-input w-full @error('next_maintenance_date') border-red-500 @enderror">
                    @error('next_maintenance_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="form-textarea w-full @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                <a href="{{ route('equipment.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Save Equipment
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
