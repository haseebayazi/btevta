@extends('layouts.app')
@section('title', 'Departure Checklist')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-clipboard-list"></i> Departure Checklist</h2>
            <p class="text-muted">
                {{ $departure->candidate->name ?? 'Candidate' }} &mdash;
                {{ $departure->candidate->trade->name ?? '' }}
                @if($departure->candidate->campus)
                | {{ $departure->candidate->campus->name }}
                @endif
            </p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('departure.enhanced-dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    <!-- Overall Progress -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <strong>Overall Readiness</strong>
                <span>{{ $checklist['completed'] }}/{{ $checklist['total'] }} Complete</span>
            </div>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar {{ $checklist['percentage'] == 100 ? 'bg-success' : 'bg-primary' }}"
                     style="width: {{ $checklist['percentage'] }}%">
                    {{ $checklist['percentage'] }}%
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- PTN Card -->
        <div class="col-md-6 mb-4">
            <div class="card {{ $checklist['items']['ptn']['complete'] ? 'border-success' : 'border-warning' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>PTN (Permission to Depart)</strong>
                    @if($checklist['items']['ptn']['complete'])
                    <span class="badge badge-success"><i class="fas fa-check"></i> Complete</span>
                    @else
                    <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Pending</span>
                    @endif
                </div>
                <div class="card-body">
                    @php $ptn = $checklist['items']['ptn']['details']; @endphp
                    @if($ptn->isIssued())
                    <p><strong>Status:</strong> <span class="badge badge-success">Issued</span></p>
                    <p><strong>Issued Date:</strong> {{ $ptn->issuedDate }}</p>
                    @if($ptn->expiryDate)
                    <p><strong>Expiry Date:</strong>
                        <span class="{{ $ptn->isExpired() ? 'text-danger' : '' }}">{{ $ptn->expiryDate }}</span>
                    </p>
                    @endif
                    @if($ptn->evidencePath)
                    <p><small class="text-muted">Evidence uploaded</small></p>
                    @endif
                    @endif

                    @can('update', $departure)
                    <form action="{{ route('departure.update-ptn', $departure) }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="form-group">
                            <label>PTN Number <span class="text-danger">*</span></label>
                            <input type="text" name="ptn_number" class="form-control form-control-sm"
                                   placeholder="Enter PTN number" required>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <label>Issued Date <span class="text-danger">*</span></label>
                                <input type="date" name="issued_date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label>Evidence (PDF/Image)</label>
                            <input type="file" name="evidence" class="form-control-file form-control-sm"
                                   accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mt-2">
                            <i class="fas fa-save"></i> Update PTN
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Protector Card -->
        <div class="col-md-6 mb-4">
            <div class="card {{ $checklist['items']['protector']['complete'] ? 'border-success' : 'border-warning' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Protector Clearance</strong>
                    @if($checklist['items']['protector']['complete'])
                    <span class="badge badge-success"><i class="fas fa-check"></i> Complete</span>
                    @else
                    <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Pending</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($departure->protector_status)
                    <p>
                        <strong>Current Status:</strong>
                        <span class="badge badge-{{ $departure->protector_status->color() }}">
                            <i class="{{ $departure->protector_status->icon() }}"></i>
                            {{ $departure->protector_status->label() }}
                        </span>
                    </p>
                    @endif
                    @if($departure->protector_details)
                    @if(!empty($departure->protector_details['applied_date']))
                    <p><strong>Applied:</strong> {{ $departure->protector_details['applied_date'] }}</p>
                    @endif
                    @if(!empty($departure->protector_details['completion_date']))
                    <p><strong>Completed:</strong> {{ $departure->protector_details['completion_date'] }}</p>
                    @endif
                    @if(!empty($departure->protector_details['notes']))
                    <p><strong>Notes:</strong> {{ $departure->protector_details['notes'] }}</p>
                    @endif
                    @endif

                    @can('update', $departure)
                    <form action="{{ route('departure.update-protector', $departure) }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control form-control-sm" required>
                                <option value="not_applied">Not Applied</option>
                                <option value="applied">Applied</option>
                                <option value="pending">Pending</option>
                                <option value="done">Done</option>
                                <option value="not_issued">Not Issued</option>
                                <option value="refused">Refused</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Certificate (PDF/Image)</label>
                            <input type="file" name="certificate" class="form-control-file form-control-sm"
                                   accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-save"></i> Update Protector Status
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Ticket Card -->
        <div class="col-md-12 mb-4">
            <div class="card {{ $checklist['items']['ticket']['complete'] ? 'border-success' : 'border-warning' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-ticket-alt"></i> Flight Ticket Details</strong>
                    @if($checklist['items']['ticket']['complete'])
                    <span class="badge badge-success"><i class="fas fa-check"></i> Complete</span>
                    @else
                    <span class="badge badge-warning"><i class="fas fa-exclamation"></i> Pending</span>
                    @endif
                </div>
                <div class="card-body">
                    @php $ticket = $checklist['items']['ticket']['details']; @endphp
                    @if($ticket->isComplete())
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Airline:</strong> {{ $ticket->airline }}</div>
                        <div class="col-md-3"><strong>Flight:</strong> {{ $ticket->flightNumber }}</div>
                        <div class="col-md-3"><strong>PNR:</strong> {{ $ticket->pnr ?? '-' }}</div>
                        <div class="col-md-3"><strong>Ticket No:</strong> {{ $ticket->ticketNumber ?? '-' }}</div>
                        <div class="col-md-3"><strong>Departure:</strong> {{ $ticket->departureDate }} {{ $ticket->departureTime }}</div>
                        <div class="col-md-3"><strong>Arrival:</strong> {{ $ticket->arrivalDate }} {{ $ticket->arrivalTime }}</div>
                        <div class="col-md-3"><strong>From:</strong> {{ $ticket->departureAirport }}</div>
                        <div class="col-md-3"><strong>To:</strong> {{ $ticket->arrivalAirport }}</div>
                    </div>
                    @endif

                    @can('update', $departure)
                    <form action="{{ route('departure.update-ticket', $departure) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Airline <span class="text-danger">*</span></label>
                                    <input type="text" name="airline" class="form-control form-control-sm"
                                           value="{{ $ticket->airline }}" placeholder="e.g., PIA" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Flight Number <span class="text-danger">*</span></label>
                                    <input type="text" name="flight_number" class="form-control form-control-sm"
                                           value="{{ $ticket->flightNumber }}" placeholder="e.g., PK-723" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ticket Number</label>
                                    <input type="text" name="ticket_number" class="form-control form-control-sm"
                                           value="{{ $ticket->ticketNumber }}" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>PNR</label>
                                    <input type="text" name="pnr" class="form-control form-control-sm"
                                           value="{{ $ticket->pnr }}" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Departure Date <span class="text-danger">*</span></label>
                                    <input type="date" name="departure_date" class="form-control form-control-sm"
                                           value="{{ $ticket->departureDate }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Departure Time <span class="text-danger">*</span></label>
                                    <input type="time" name="departure_time" class="form-control form-control-sm"
                                           value="{{ $ticket->departureTime }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Arrival Date <span class="text-danger">*</span></label>
                                    <input type="date" name="arrival_date" class="form-control form-control-sm"
                                           value="{{ $ticket->arrivalDate }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Arrival Time <span class="text-danger">*</span></label>
                                    <input type="time" name="arrival_time" class="form-control form-control-sm"
                                           value="{{ $ticket->arrivalTime }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Departure Airport <span class="text-danger">*</span></label>
                                    <input type="text" name="departure_airport" class="form-control form-control-sm"
                                           value="{{ $ticket->departureAirport }}" placeholder="e.g., LHE" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Arrival Airport <span class="text-danger">*</span></label>
                                    <input type="text" name="arrival_airport" class="form-control form-control-sm"
                                           value="{{ $ticket->arrivalAirport }}" placeholder="e.g., RUH" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ticket File (PDF/Image)</label>
                                    <input type="file" name="ticket_file" class="form-control-file form-control-sm"
                                           accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-save"></i> Update Ticket Details
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Briefing Card -->
        <div class="col-md-12 mb-4">
            <div class="card {{ $checklist['items']['briefing']['complete'] ? 'border-success' : 'border-warning' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-chalkboard-teacher"></i> Pre-Departure Briefing</strong>
                    @if($departure->briefing_status)
                    <span class="badge badge-{{ $departure->briefing_status->color() }}">
                        <i class="{{ $departure->briefing_status->icon() }}"></i>
                        {{ $departure->briefing_status->label() }}
                    </span>
                    @else
                    <span class="badge badge-secondary">Not Scheduled</span>
                    @endif
                </div>
                <div class="card-body">
                    @php $briefing = $checklist['items']['briefing']['details']; @endphp
                    @if($briefing->scheduledDate)
                    <p><strong>Scheduled Date:</strong> {{ $briefing->scheduledDate }}</p>
                    @endif
                    @if($briefing->completedDate)
                    <p><strong>Completed Date:</strong> {{ $briefing->completedDate }}</p>
                    <p><strong>Acknowledgment Signed:</strong>
                        {{ $briefing->acknowledgmentSigned ? 'Yes' : 'No' }}
                    </p>
                    @endif
                    @if($briefing->notes)
                    <p><strong>Notes:</strong> {{ $briefing->notes }}</p>
                    @endif
                    @if($briefing->hasDocuments())
                    <p class="text-muted"><small>Documents/video uploaded</small></p>
                    @endif

                    @can('update', $departure)
                    @if(!$checklist['items']['briefing']['complete'])
                    <!-- Schedule Form -->
                    @if(!$briefing->scheduledDate)
                    <form action="{{ route('departure.schedule-briefing', $departure) }}" method="POST" class="mt-3">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="col-md-4">
                                <label>Briefing Date <span class="text-danger">*</span></label>
                                <input type="date" name="briefing_date" class="form-control form-control-sm"
                                       min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3 mt-2">
                                <button type="submit" class="btn btn-sm btn-info">
                                    <i class="fas fa-calendar-plus"></i> Schedule Briefing
                                </button>
                            </div>
                        </div>
                    </form>
                    @endif

                    <!-- Complete Form -->
                    <form action="{{ route('departure.complete-briefing', $departure) }}" method="POST"
                          enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Briefing Document (PDF)</label>
                                    <input type="file" name="briefing_document" class="form-control-file form-control-sm"
                                           accept=".pdf">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Briefing Video (MP4/MOV/AVI, max 100MB)</label>
                                    <input type="file" name="briefing_video" class="form-control-file form-control-sm"
                                           accept=".mp4,.mov,.avi">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Acknowledgment Form (PDF/Image)</label>
                                    <input type="file" name="acknowledgment_file" class="form-control-file form-control-sm"
                                           accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input type="checkbox" name="acknowledgment_signed" value="1"
                                           class="form-check-input" id="ack_signed" required>
                                    <label class="form-check-label" for="ack_signed">
                                        <strong>Candidate has acknowledged the pre-departure briefing</strong>
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success mt-2">
                            <i class="fas fa-check-circle"></i> Mark Briefing Complete
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @can('update', $departure)
    <div class="card mb-4">
        <div class="card-body">
            @if($checklist['can_mark_ready'] && (!$departure->departure_status || $departure->departure_status->value === 'processing'))
            <form action="{{ route('departure.mark-ready', $departure) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info btn-lg"
                        onclick="return confirm('Mark this candidate as Ready to Depart?')">
                    <i class="fas fa-plane-departure"></i> Mark Ready to Depart
                </button>
            </form>
            @elseif($departure->departure_status && $departure->departure_status->value === 'ready_to_depart')
            <form action="{{ route('departure.record-departure-actual', $departure) }}" method="POST" class="d-inline">
                @csrf
                <div class="form-inline">
                    <label class="mr-2"><strong>Actual Departure Time:</strong></label>
                    <input type="datetime-local" name="actual_departure_time" class="form-control form-control-sm mr-2">
                    <button type="submit" class="btn btn-success btn-lg"
                            onclick="return confirm('Record actual departure for this candidate?')">
                        <i class="fas fa-plane"></i> Record Departure
                    </button>
                </div>
            </form>
            @elseif($departure->departure_status && $departure->departure_status->value === 'departed')
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Departed</strong> on {{ $departure->departed_at?->format('d M Y H:i') ?? '-' }}
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Complete all checklist items above before marking ready to depart.
                <br><small>Remaining: {{ $checklist['total'] - $checklist['completed'] }} item(s) to complete</small>
            </div>
            @endif
        </div>
    </div>
    @endcan
</div>
@endsection
