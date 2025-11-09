@extends('layouts.app')
@section('title', 'OEP Performance Report')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">OEP Performance Report</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>OEP</th>
                    <th>Total Candidates</th>
                    <th>Departed</th>
                    <th>Success Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($oeps as $oep)
                <tr>
                    <td>{{ $oep->name }}</td>
                    <td>{{ $oep->candidates_count }}</td>
                    <td>{{ $oep->departed_count }}</td>
                    <td>{{ $oep->candidates_count > 0 ? round(($oep->departed_count/$oep->candidates_count)*100) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
