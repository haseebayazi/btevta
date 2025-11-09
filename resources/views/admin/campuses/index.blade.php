@extends('layouts.app')
@section('title', 'Campus Management')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Campus Management</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('admin.campuses.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Campus
            </a>
        </div>
    </div>

    @if($campuses->count())
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Campus Name</th>
                            <th>Location</th>
                            <th>Province</th>
                            <th>District</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Candidates</th>
                            <th>Batches</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campuses as $campus)
                            <tr>
                                <td><strong>{{ $campus->name }}</strong></td>
                                <td>{{ $campus->location }}</td>
                                <td>{{ $campus->province }}</td>
                                <td>{{ $campus->district }}</td>
                                <td>{{ $campus->contact_person }}</td>
                                <td>{{ $campus->phone }}</td>
                                <td>
                                    <span class="badge badge-primary">{{ $campus->candidates_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $campus->batches_count ?? 0 }}</span>
                                </td>
                                <td>
                                    @if($campus->is_active ?? true)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.campuses.show', $campus->id) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.campuses.edit', $campus->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.campuses.destroy', $campus->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this campus?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $campuses->links() }}
        </div>
    @else
        <div class="alert alert-info">No campuses found. Click "Add New Campus" to get started.</div>
    @endif
</div>
@endsection
