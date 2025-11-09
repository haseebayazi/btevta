@extends('layouts.app')
@section('title', 'Complaint Analysis Report')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Complaint Analysis</h2>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">By Status</div>
                <div class="card-body">
                    <table class="table">
                        <tr><td>Status</td><td>Count</td></tr>
                        @foreach($complaintsByStatus as $item)
                        <tr><td>{{ ucfirst($item->status) }}</td><td>{{ $item->count }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Overdue</div>
                <div class="card-body">
                    <h3 class="text-danger">{{ $overdueComplaints }}</h3>
                    <p>Complaints exceeding SLA</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
