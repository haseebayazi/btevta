@extends('layouts.app')
@section('title', 'Batch Report')
@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Batch {{ $batch->batch_number }} Report</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Attendance %</th>
                    <th>Pass/Fail</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batch->candidates as $candidate)
                <tr>
                    <td>{{ $candidate->name }}</td>
                    <td>
                        @php
                        $present = $candidate->attendances()->where('status', 'present')->count();
                        $total = $candidate->attendances()->count();
                        @endphp
                        {{ $total > 0 ? round(($present/$total)*100) : 0 }}%
                    </td>
                    <td>
                        @php
                        $passed = $candidate->assessments()->where('result', 'pass')->count();
                        @endphp
                        {{ $passed > 0 ? 'PASS' : 'FAIL' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
