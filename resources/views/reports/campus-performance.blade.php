@extends('layouts.app')
@section('title', 'Campus Performance Report')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Campus Performance Report</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Campus</th>
                    <th>Total Candidates</th>
                    <th>Registered</th>
                    <th>In Training</th>
                    <th>Departed</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campuses as $campus)
                <tr>
                    <td>{{ $campus->name }}</td>
                    <td>{{ $campus->candidates_count }}</td>
                    <td>{{ $campus->registered_count }}</td>
                    <td>{{ $campus->training_count }}</td>
                    <td>{{ $campus->departed_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
