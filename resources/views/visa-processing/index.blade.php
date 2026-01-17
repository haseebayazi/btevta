@extends('layouts.app')

@section('title', 'Visa Processing')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Visa Processing</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('visa-processing.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Visa Process
            </a>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ $message }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Candidates in Visa Processing</h5>
        </div>
        <div class="card-body">
            @if($candidates->count())
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>TheLeap ID</th>
                                <th>Name</th>
                                <th>OEP</th>
                                <th>Current Stage</th>
                                <th>Interview</th>
                                <th>Trade Test</th>
                                <th>Medical</th>
                                <th>Visa Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $candidate)
                                <tr>
                                    <td>{{ $candidate->btevta_id }}</td>
                                    <td>{{ $candidate->name }}</td>
                                    <td>{{ $candidate->oep?->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($candidate->visaProcess)
                                            <span class="badge badge-info">
                                                {{ ucfirst(str_replace('_', ' ', $candidate->visaProcess->current_stage ?? 'Pending')) }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">Not Started</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($candidate->visaProcess?->interview_result)
                                            <span class="badge badge-{{ $candidate->visaProcess->interview_result === 'pass' ? 'success' : 'danger' }}">
                                                {{ ucfirst($candidate->visaProcess->interview_result) }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($candidate->visaProcess?->trade_test_result)
                                            <span class="badge badge-{{ $candidate->visaProcess->trade_test_result === 'pass' ? 'success' : 'danger' }}">
                                                {{ ucfirst($candidate->visaProcess->trade_test_result) }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($candidate->visaProcess?->gamca_result)
                                            <span class="badge badge-success">Done</span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($candidate->visaProcess?->visa_number)
                                            <span class="badge badge-success">
                                                {{ $candidate->visaProcess->visa_number }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('visa-processing.show', $candidate) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('visa-processing.edit', $candidate->visaProcess ?? '') }}" class="btn btn-sm btn-warning" {{ !$candidate->visaProcess ? 'disabled' : '' }}>
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $candidates->links() }}
            @else
                <div class="alert alert-info">
                    No candidates in visa processing.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection