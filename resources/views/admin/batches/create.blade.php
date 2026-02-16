@extends('layouts.app')
@section('title', 'Create Batch')
@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
        <div>
            <nav class="text-sm text-gray-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span class="mx-1">/</span>
                <a href="{{ route('admin.batches.index') }}" class="hover:text-blue-600">Batches</a>
                <span class="mx-1">/</span>
                <span class="text-gray-700">Create New Batch</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Create New Batch</h2>
        </div>
        <a href="{{ route('admin.batches.index') }}" class="mt-3 sm:mt-0 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.batches.store') }}">
                @csrf

                {{-- Basic Information --}}
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                    <div class="bg-blue-600 text-white px-5 py-3">
                        <h5 class="font-semibold"><i class="fas fa-info-circle mr-2"></i>Basic Information</h5>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="batch_code" class="block text-sm font-medium text-gray-700 mb-1">Batch Code <span class="text-red-500">*</span></label>
                                <input type="text" id="batch_code" name="batch_code"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('batch_code') border-red-500 @enderror"
                                       value="{{ old('batch_code') }}" placeholder="e.g., BATCH-2026-001" required>
                                <p class="text-xs text-gray-400 mt-1">Unique identifier for this batch</p>
                                @error('batch_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Batch Name</label>
                                <input type="text" id="name" name="name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                       value="{{ old('name') }}" placeholder="e.g., Electrician Batch Jan 2026">
                                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="trade_id" class="block text-sm font-medium text-gray-700 mb-1">Trade <span class="text-red-500">*</span></label>
                                <select id="trade_id" name="trade_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('trade_id') border-red-500 @enderror" required>
                                    <option value="">-- Select Trade --</option>
                                    @foreach($trades as $id => $name)
                                        <option value="{{ $id }}" {{ old('trade_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('trade_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="campus_id" class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                                <select id="campus_id" name="campus_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('campus_id') border-red-500 @enderror" required>
                                    <option value="">-- Select Campus --</option>
                                    @foreach($campuses as $id => $name)
                                        <option value="{{ $id }}" {{ old('campus_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('campus_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="oep_id" class="block text-sm font-medium text-gray-700 mb-1">OEP <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <select id="oep_id" name="oep_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('oep_id') border-red-500 @enderror">
                                    <option value="">-- Select OEP --</option>
                                    @foreach($oeps as $id => $name)
                                        <option value="{{ $id }}" {{ old('oep_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('oep_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule & Capacity --}}
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                    <div class="px-5 py-3 border-b">
                        <h5 class="font-semibold text-gray-800"><i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Schedule & Capacity</h5>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                                <input type="date" id="start_date" name="start_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('start_date') border-red-500 @enderror"
                                       value="{{ old('start_date') }}" required>
                                @error('start_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <input type="date" id="end_date" name="end_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('end_date') border-red-500 @enderror"
                                       value="{{ old('end_date') }}">
                                @error('end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity <span class="text-red-500">*</span></label>
                                <input type="number" id="capacity" name="capacity"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('capacity') border-red-500 @enderror"
                                       value="{{ old('capacity', 30) }}" min="1" max="500" required>
                                @error('capacity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror" required>
                                    @foreach(\App\Models\Batch::getStatuses() as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', \App\Models\Batch::STATUS_PLANNED) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="intake_period" class="block text-sm font-medium text-gray-700 mb-1">Intake Period</label>
                                <input type="text" id="intake_period" name="intake_period"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('intake_period') border-red-500 @enderror"
                                       value="{{ old('intake_period') }}" placeholder="e.g., January 2026">
                                @error('intake_period')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="district" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                                <input type="text" id="district" name="district"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('district') border-red-500 @enderror"
                                       value="{{ old('district') }}" placeholder="e.g., Lahore">
                                @error('district')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Staff Assignment --}}
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                    <div class="px-5 py-3 border-b">
                        <h5 class="font-semibold text-gray-800"><i class="fas fa-user-tie mr-2 text-blue-500"></i>Staff Assignment <span class="text-gray-400 font-normal text-sm">(Optional)</span></h5>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="trainer_id" class="block text-sm font-medium text-gray-700 mb-1">Trainer</label>
                                <select id="trainer_id" name="trainer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('trainer_id') border-red-500 @enderror">
                                    <option value="">-- Select Trainer --</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('trainer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('trainer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="coordinator_id" class="block text-sm font-medium text-gray-700 mb-1">Coordinator</label>
                                <select id="coordinator_id" name="coordinator_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('coordinator_id') border-red-500 @enderror">
                                    <option value="">-- Select Coordinator --</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('coordinator_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('coordinator_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Information --}}
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                    <div class="px-5 py-3 border-b">
                        <h5 class="font-semibold text-gray-800"><i class="fas fa-file-alt mr-2 text-blue-500"></i>Additional Information</h5>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                            <input type="text" id="specialization" name="specialization"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('specialization') border-red-500 @enderror"
                                   value="{{ old('specialization') }}" placeholder="e.g., Industrial Electrician, Domestic Wiring">
                            @error('specialization')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                                      placeholder="Additional notes about this batch...">{{ old('description') }}</textarea>
                            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-between">
                    <a href="{{ route('admin.batches.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm">
                        <i class="fas fa-save mr-1"></i> Create Batch
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="bg-cyan-500 text-white px-5 py-3">
                    <h5 class="font-semibold"><i class="fas fa-lightbulb mr-2"></i>Quick Tips</h5>
                </div>
                <div class="p-4">
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i> Use a unique batch code like BATCH-2026-001</li>
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i> Start with "Planned" status, change to "Active" when training begins</li>
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i> Set capacity to the maximum candidates for this batch</li>
                        <li class="flex items-start"><i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i> Trainer and coordinator can be assigned later</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-3 border-b">
                    <h5 class="font-semibold text-gray-800"><i class="fas fa-asterisk mr-2 text-red-500 text-xs"></i>Required Fields</h5>
                </div>
                <div class="p-4">
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li><i class="fas fa-circle text-red-400 text-xs mr-2"></i>Batch Code</li>
                        <li><i class="fas fa-circle text-red-400 text-xs mr-2"></i>Trade</li>
                        <li><i class="fas fa-circle text-red-400 text-xs mr-2"></i>Campus</li>
                        <li><i class="fas fa-circle text-red-400 text-xs mr-2"></i>Start Date</li>
                        <li><i class="fas fa-circle text-red-400 text-xs mr-2"></i>Capacity</li>
                        <li><i class="fas fa-circle text-red-400 text-xs mr-2"></i>Status</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection