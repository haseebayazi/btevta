@extends('layouts.app')
@section('title', 'Edit Complaint')
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Edit Complaint</h1>
        <p class="text-gray-600 mt-2">Complaint #{{ $complaint->complaint_number }}</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Complaint Info Card -->
        <div class="card">
            <h3 class="text-lg font-bold mb-4">Complaint Details</h3>
            <div class="space-y-2 text-sm">
                <p><strong>Number:</strong> {{ $complaint->complaint_number }}</p>
                <p><strong>Status:</strong>
                    <span class="badge badge-{{ $complaint->status_color ?? 'secondary' }}">
                        {{ ucfirst($complaint->status) }}
                    </span>
                </p>
                <p><strong>Priority:</strong>
                    <span class="badge badge-{{ $complaint->priority_color ?? 'secondary' }}">
                        {{ ucfirst($complaint->priority) }}
                    </span>
                </p>
                <p><strong>Category:</strong> {{ ucfirst($complaint->category) }}</p>
                <p><strong>Submitted:</strong> {{ $complaint->created_at->format('M d, Y') }}</p>
                @if($complaint->complainant_name)
                <p><strong>Complainant:</strong> {{ $complaint->complainant_name }}</p>
                @endif
            </div>
        </div>

        <!-- Edit Form -->
        <div class="md:col-span-2">
            <div class="card">
                <form action="{{ route('complaints.update', $complaint) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="form-group md:col-span-2">
                            <label for="subject" class="required">Subject</label>
                            <input type="text"
                                   id="subject"
                                   name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject', $complaint->subject) }}"
                                   required>
                            @error('subject')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="category" class="required">Category</label>
                            <select id="category"
                                    name="category"
                                    class="form-control @error('category') is-invalid @enderror"
                                    required>
                                <option value="">Select Category</option>
                                <option value="screening" {{ old('category', $complaint->category) == 'screening' ? 'selected' : '' }}>Screening</option>
                                <option value="training" {{ old('category', $complaint->category) == 'training' ? 'selected' : '' }}>Training</option>
                                <option value="visa" {{ old('category', $complaint->category) == 'visa' ? 'selected' : '' }}>Visa</option>
                                <option value="salary" {{ old('category', $complaint->category) == 'salary' ? 'selected' : '' }}>Salary</option>
                                <option value="conduct" {{ old('category', $complaint->category) == 'conduct' ? 'selected' : '' }}>Conduct</option>
                                <option value="facility" {{ old('category', $complaint->category) == 'facility' ? 'selected' : '' }}>Facility</option>
                                <option value="medical" {{ old('category', $complaint->category) == 'medical' ? 'selected' : '' }}>Medical</option>
                                <option value="document" {{ old('category', $complaint->category) == 'document' ? 'selected' : '' }}>Document</option>
                                <option value="other" {{ old('category', $complaint->category) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="priority" class="required">Priority</label>
                            <select id="priority"
                                    name="priority"
                                    class="form-control @error('priority') is-invalid @enderror"
                                    required>
                                <option value="">Select Priority</option>
                                <option value="low" {{ old('priority', $complaint->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', $complaint->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority', $complaint->priority) == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('priority', $complaint->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            @error('priority')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="status" class="required">Status</label>
                            <select id="status"
                                    name="status"
                                    class="form-control @error('status') is-invalid @enderror"
                                    required>
                                <option value="">Select Status</option>
                                <option value="registered" {{ old('status', $complaint->status) == 'registered' ? 'selected' : '' }}>Registered</option>
                                <option value="investigating" {{ old('status', $complaint->status) == 'investigating' ? 'selected' : '' }}>Investigating</option>
                                <option value="resolved" {{ old('status', $complaint->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ old('status', $complaint->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="assigned_to">Assign To</label>
                            <select id="assigned_to"
                                    name="assigned_to"
                                    class="form-control @error('assigned_to') is-invalid @enderror">
                                <option value="">Unassigned</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $complaint->assigned_to) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label for="description" class="required">Description</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="5"
                                      required>{{ old('description', $complaint->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        @if(auth()->user()->isAdmin())
                        <div class="form-group md:col-span-2">
                            <label for="admin_notes">Admin Notes (Internal Only)</label>
                            <textarea id="admin_notes"
                                      name="admin_notes"
                                      class="form-control @error('admin_notes') is-invalid @enderror"
                                      rows="3">{{ old('admin_notes', $complaint->admin_notes) }}</textarea>
                            @error('admin_notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        @endif
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Complaint
                        </button>
                        <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
