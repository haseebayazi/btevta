@extends('layouts.app')
@section('title', 'OEPs Management')
@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>OEPs Management</h2>
        </div>
        <div class="col-md-4 text-right">
            @can('create', App\Models\Oep::class)
            <a href="{{ route('admin.oeps.create') }}" class="btn btn-primary">+ Add New OEP</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Country</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($oeps as $oep)
                        <tr>
                            <td>{{ $oep->id }}</td>
                            <td>{{ $oep->name }}</td>
                            <td><span class="badge badge-info">{{ $oep->code ?? 'N/A' }}</span></td>
                            <td>{{ $oep->country ?? 'N/A' }}</td>
                            <td>{{ $oep->contact_person ?? 'N/A' }}</td>
                            <td>
                                @if($oep->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @can('view', $oep)
                                <a href="{{ route('admin.oeps.show', $oep->id) }}" class="btn btn-sm btn-info">View</a>
                                @endcan
                                @can('update', $oep)
                                <a href="{{ route('admin.oeps.edit', $oep->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endcan
                                @can('delete', $oep)
                                <form method="POST" action="{{ route('admin.oeps.destroy', $oep->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No OEPs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $oeps->links() }}
    </div>
</div>
@endsection