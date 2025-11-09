@extends('layouts.app')
@section('title', 'Custom Report Builder')
@section('content')
<div class="container">
    <h2 class="mb-4">Build Custom Report</h2>
    <form method="POST" action="{{ route('reports.generate-custom') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>Campus</label>
                <select name="campus_id" class="form-control">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                    <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    @foreach($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" class="form-control">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" class="form-control">
            </div>
            <div class="form-group">
                <label>Format</label>
                <select name="format" class="form-control" required>
                    <option value="view">View</option>
                    <option value="excel">Excel</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </div>
    </form>
</div>
@endsection
