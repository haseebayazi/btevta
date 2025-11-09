@extends('layouts.app')
@section('title', 'Trade Management')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Trade Management</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('trades.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Trade
            </a>
        </div>
    </div>

    @if($trades->count())
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Trade Name</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Duration (weeks)</th>
                            <th>Candidates</th>
                            <th>Batches</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trades as $trade)
                            <tr>
                                <td>{{ $trade->name }}</td>
                                <td><span class="badge badge-info">{{ $trade->code }}</span></td>
                                <td>{{ $trade->category }}</td>
                                <td>{{ $trade->duration_weeks }}</td>
                                <td><span class="badge badge-primary">{{ $trade->candidates_count }}</span></td>
                                <td><span class="badge badge-success">{{ $trade->batches_count }}</span></td>
                                <td>
                                    <a href="{{ route('trades.show', $trade->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('trades.edit', $trade->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('trades.destroy', $trade->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete trade?')">
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
            {{ $trades->links() }}
        </div>
    @else
        <div class="alert alert-info">No trades found.</div>
    @endif
</div>
@endsection