@extends('layouts.app')
@section('title', 'Custom Report Results')
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Report Results</h2>
            <a href="{{ route('reports.custom-report') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>TheLeap ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Campus</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $item)
                    <tr>
                        <td>{{ $item->btevta_id }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->status_label }}</td>
                        <td>{{ $item->campus?->name ?? 'N/A' }}</td>
                        <td>{{ $item->created_at->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
