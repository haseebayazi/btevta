@extends('layouts.app')
@section('title', 'Edit Screening')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Edit Screening Record</h1>
                <p class="text-gray-600 mt-2">Update screening details for {{ $candidate->name }}</p>
            </div>
            <a href="{{ route('screening.index') }}" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Candidate Info Card -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold mb-4 border-b pb-2">Candidate Information</h3>
            <div class="space-y-3 text-sm">
                <p><strong class="text-gray-600">BTEVTA ID:</strong><br>
                    <span class="font-mono">{{ $candidate->btevta_id }}</span>
                </p>
                <p><strong class="text-gray-600">Name:</strong><br> {{ $candidate->name }}</p>
                <p><strong class="text-gray-600">Father:</strong><br> {{ $candidate->father_name }}</p>
                <p><strong class="text-gray-600">CNIC:</strong><br> {{ $candidate->formatted_cnic ?? $candidate->cnic }}</p>
                <p><strong class="text-gray-600">Phone:</strong><br> {{ $candidate->phone }}</p>
                <p><strong class="text-gray-600">Status:</strong><br>
                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                        {{ $candidate->status == 'registered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($candidate->status) }}
                    </span>
                </p>
            </div>

            <!-- Screening History -->
            <h4 class="text-md font-bold mt-6 mb-3 border-t pt-4">Screening History</h4>
            @if($candidate->screenings->count() > 0)
                <div class="space-y-2 text-sm">
                    @foreach($candidate->screenings as $s)
                        <div class="p-2 rounded {{ $s->id == $screening->id ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50' }}">
                            <div class="flex justify-between">
                                <span class="font-medium">{{ ucfirst($s->screening_type ?? 'N/A') }}</span>
                                <span class="text-xs px-2 py-1 rounded
                                    {{ $s->status == 'passed' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $s->status == 'failed' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $s->status == 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                    {{ $s->status == 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $s->status ?? 'N/A')) }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $s->screened_at ? $s->screened_at->format('M d, Y H:i') : 'N/A' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No previous screenings</p>
            @endif
        </div>

        <!-- Screening Form -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <form action="{{ route('screening.update', $candidate->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-2 gap-4">
                        <!-- Screening Type -->
                        <div>
                            <label for="screening_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Screening Type <span class="text-red-600">*</span>
                            </label>
                            <select id="screening_type" name="screening_type" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('screening_type') border-red-500 @enderror">
                                <option value="">Select Type</option>
                                <option value="call" {{ old('screening_type', $screening->screening_type) == 'call' ? 'selected' : '' }}>Phone Call</option>
                                <option value="desk" {{ old('screening_type', $screening->screening_type) == 'desk' ? 'selected' : '' }}>Desk Review</option>
                                <option value="document" {{ old('screening_type', $screening->screening_type) == 'document' ? 'selected' : '' }}>Document Verification</option>
                                <option value="interview" {{ old('screening_type', $screening->screening_type) == 'interview' ? 'selected' : '' }}>In-Person Interview</option>
                            </select>
                            @error('screening_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Screening Date -->
                        <div>
                            <label for="screened_at" class="block text-sm font-medium text-gray-700 mb-2">
                                Screening Date & Time <span class="text-red-600">*</span>
                            </label>
                            <input type="datetime-local" id="screened_at" name="screened_at" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('screened_at') border-red-500 @enderror"
                                   value="{{ old('screened_at', $screening->screened_at ? $screening->screened_at->format('Y-m-d\TH:i') : '') }}">
                            @error('screened_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Call Duration -->
                        <div>
                            <label for="call_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                Duration (minutes)
                            </label>
                            <input type="number" id="call_duration" name="call_duration" min="1"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('call_duration') border-red-500 @enderror"
                                   value="{{ old('call_duration', $screening->call_duration) }}"
                                   placeholder="e.g., 15">
                            @error('call_duration')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Screening Status <span class="text-red-600">*</span>
                            </label>
                            <select id="status" name="status" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('status') border-red-500 @enderror">
                                <option value="">Select Status</option>
                                <option value="pending" {{ old('status', $screening->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_progress" {{ old('status', $screening->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="passed" {{ old('status', $screening->status) == 'passed' ? 'selected' : '' }}>Passed</option>
                                <option value="failed" {{ old('status', $screening->status) == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="deferred" {{ old('status', $screening->status) == 'deferred' ? 'selected' : '' }}>Deferred</option>
                                <option value="cancelled" {{ old('status', $screening->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remarks -->
                        <div class="md:col-span-2">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
                                Remarks / Notes
                            </label>
                            <textarea id="remarks" name="remarks" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('remarks') border-red-500 @enderror"
                                      placeholder="Enter any observations, notes, or follow-up actions...">{{ old('remarks', $screening->remarks) }}</textarea>
                            @error('remarks')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-6 mt-6 border-t border-gray-200">
                        <a href="{{ route('screening.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Update Screening
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
