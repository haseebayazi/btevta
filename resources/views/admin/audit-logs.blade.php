@extends('layouts.app')
@section('title', 'Audit Logs')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Audit Logs</h2>

    <div class="card">
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row mb-3">
                <div class="col-md-3">
                    <input type="text" name="user" placeholder="Filter by user..." class="form-control" value="{{ request('user') }}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="action" placeholder="Filter by action..." class="form-control" value="{{ request('action') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2024-11-01 10:30</td>
                        <td>Admin User</td>
                        <td><span class="badge badge-info">CREATE</span></td>
                        <td>Candidate</td>
                        <td>Created new candidate record</td>
                        <td>192.168.1.1</td>
                    </tr>
                    <tr>
                        <td>2024-11-01 09:15</td>
                        <td>Campus Admin</td>
                        <td><span class="badge badge-warning">UPDATE</span></td>
                        <td>Batch</td>
                        <td>Updated batch status</td>
                        <td>192.168.1.2</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection