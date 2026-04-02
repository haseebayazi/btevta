@extends('layouts.app')
@section('title', 'Departure Checklist')
@section('content')
@php
    $colorMap = [
        'success'   => 'bg-green-100 text-green-800',
        'warning'   => 'bg-yellow-100 text-yellow-800',
        'info'      => 'bg-cyan-100 text-cyan-800',
        'secondary' => 'bg-gray-100 text-gray-700',
        'danger'    => 'bg-red-100 text-red-800',
        'primary'   => 'bg-blue-100 text-blue-800',
    ];
@endphp
<div class="container mx-auto px-4 py-6 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-clipboard-list mr-2 text-blue-600"></i>Departure Checklist
            </h2>
            <p class="text-gray-500 mt-1">
                {{ $departure->candidate->name ?? 'Candidate' }}
                &mdash;
                {{ $departure->candidate->trade->name ?? '' }}
                @if($departure->candidate->campus)
                    <span class="mx-1 text-gray-300">|</span> {{ $departure->candidate->campus->name }}
                @endif
            </p>
        </div>
        <a href="{{ route('departure.enhanced-dashboard') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between"
         x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button @click="show = false" class="text-green-500 hover:text-green-700 ml-4">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between"
         x-data="{ show: true }" x-show="show">
        <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
        <button @click="show = false" class="text-red-500 hover:text-red-700 ml-4">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    {{-- Overall Progress --}}
    <div class="bg-white rounded-xl shadow-sm border p-5">
        <div class="flex justify-between items-center mb-3">
            <span class="font-semibold text-gray-800">Overall Readiness</span>
            <span class="text-sm text-gray-600">{{ $checklist['completed'] }}/{{ $checklist['total'] }} Complete</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-5">
            <div class="h-5 rounded-full text-center text-white text-xs leading-5 font-medium transition-all duration-500
                        {{ $checklist['percentage'] == 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                 style="width: {{ $checklist['percentage'] }}%">
                {{ $checklist['percentage'] }}%
            </div>
        </div>
    </div>

    {{-- Checklist Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- PTN Card --}}
        <div class="bg-white rounded-xl shadow-sm border {{ $checklist['items']['ptn']['complete'] ? 'border-green-300' : 'border-yellow-300' }}">
            <div class="px-5 py-3 border-b {{ $checklist['items']['ptn']['complete'] ? 'bg-green-50' : 'bg-yellow-50' }} flex justify-between items-center rounded-t-xl">
                <span class="font-semibold text-gray-800">PTN (Permission to Depart)</span>
                @if($checklist['items']['ptn']['complete'])
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> Complete
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation mr-1"></i> Pending
                    </span>
                @endif
            </div>
            <div class="p-5">
                @php $ptn = $checklist['items']['ptn']['details']; @endphp
                @if($ptn->isIssued())
                <div class="space-y-1 mb-4 text-sm">
                    <p><span class="font-medium text-gray-700">Status:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-1">Issued</span>
                    </p>
                    <p><span class="font-medium text-gray-700">Issued Date:</span>
                        <span class="ml-1 text-gray-600">{{ $ptn->issuedDate }}</span>
                    </p>
                    @if($ptn->expiryDate)
                    <p><span class="font-medium text-gray-700">Expiry Date:</span>
                        <span class="ml-1 {{ $ptn->isExpired() ? 'text-red-600 font-medium' : 'text-gray-600' }}">{{ $ptn->expiryDate }}</span>
                    </p>
                    @endif
                    @if($ptn->evidencePath)
                    <p class="text-gray-500 text-xs"><i class="fas fa-paperclip mr-1"></i>Evidence uploaded</p>
                    @endif
                </div>
                @endif

                @can('update', $departure)
                <form action="{{ route('departure.update-ptn', $departure) }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PTN Number <span class="text-red-500">*</span></label>
                        <input type="text" name="ptn_number"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter PTN number" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Issued Date <span class="text-red-500">*</span></label>
                            <input type="date" name="issued_date"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                            <input type="date" name="expiry_date"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Evidence (PDF/Image)</label>
                        <input type="file" name="evidence"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                               accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i> Update PTN
                    </button>
                </form>
                @endcan
            </div>
        </div>

        {{-- Protector Card --}}
        <div class="bg-white rounded-xl shadow-sm border {{ $checklist['items']['protector']['complete'] ? 'border-green-300' : 'border-yellow-300' }}">
            <div class="px-5 py-3 border-b {{ $checklist['items']['protector']['complete'] ? 'bg-green-50' : 'bg-yellow-50' }} flex justify-between items-center rounded-t-xl">
                <span class="font-semibold text-gray-800">Protector Clearance</span>
                @if($checklist['items']['protector']['complete'])
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> Complete
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation mr-1"></i> Pending
                    </span>
                @endif
            </div>
            <div class="p-5">
                @if($departure->protector_status)
                <div class="mb-4 text-sm">
                    <p class="mb-1">
                        <span class="font-medium text-gray-700">Current Status:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-1 {{ $colorMap[$departure->protector_status->color()] ?? 'bg-gray-100 text-gray-700' }}">
                            <i class="{{ $departure->protector_status->icon() }} mr-1"></i>
                            {{ $departure->protector_status->label() }}
                        </span>
                    </p>
                    @if($departure->protector_details)
                        @if(!empty($departure->protector_details['applied_date']))
                        <p><span class="font-medium text-gray-700">Applied:</span>
                            <span class="ml-1 text-gray-600">{{ $departure->protector_details['applied_date'] }}</span>
                        </p>
                        @endif
                        @if(!empty($departure->protector_details['completion_date']))
                        <p><span class="font-medium text-gray-700">Completed:</span>
                            <span class="ml-1 text-gray-600">{{ $departure->protector_details['completion_date'] }}</span>
                        </p>
                        @endif
                        @if(!empty($departure->protector_details['notes']))
                        <p><span class="font-medium text-gray-700">Notes:</span>
                            <span class="ml-1 text-gray-600">{{ $departure->protector_details['notes'] }}</span>
                        </p>
                        @endif
                    @endif
                </div>
                @endif

                @can('update', $departure)
                <form action="{{ route('departure.update-protector', $departure) }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                        <select name="status"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="not_applied">Not Applied</option>
                            <option value="applied">Applied</option>
                            <option value="pending">Pending</option>
                            <option value="done">Done</option>
                            <option value="not_issued">Not Issued</option>
                            <option value="refused">Refused</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Optional notes"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Certificate (PDF/Image)</label>
                        <input type="file" name="certificate"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                               accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i> Update Protector Status
                    </button>
                </form>
                @endcan
            </div>
        </div>

    </div>{{-- end 2-col grid --}}

    {{-- Ticket Card --}}
    <div class="bg-white rounded-xl shadow-sm border {{ $checklist['items']['ticket']['complete'] ? 'border-green-300' : 'border-yellow-300' }}">
        <div class="px-5 py-3 border-b {{ $checklist['items']['ticket']['complete'] ? 'bg-green-50' : 'bg-yellow-50' }} flex justify-between items-center rounded-t-xl">
            <span class="font-semibold text-gray-800"><i class="fas fa-ticket-alt mr-2"></i>Flight Ticket Details</span>
            @if($checklist['items']['ticket']['complete'])
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check mr-1"></i> Complete
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <i class="fas fa-exclamation mr-1"></i> Pending
                </span>
            @endif
        </div>
        <div class="p-5">
            @php $ticket = $checklist['items']['ticket']['details']; @endphp
            @if($ticket->isComplete())
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 p-4 bg-gray-50 rounded-lg text-sm">
                <div><span class="text-gray-500 block">Airline</span><span class="font-medium">{{ $ticket->airline }}</span></div>
                <div><span class="text-gray-500 block">Flight</span><span class="font-medium">{{ $ticket->flightNumber }}</span></div>
                <div><span class="text-gray-500 block">PNR</span><span class="font-medium">{{ $ticket->pnr ?? '-' }}</span></div>
                <div><span class="text-gray-500 block">Ticket No.</span><span class="font-medium">{{ $ticket->ticketNumber ?? '-' }}</span></div>
                <div><span class="text-gray-500 block">Departure</span><span class="font-medium">{{ $ticket->departureDate }} {{ $ticket->departureTime }}</span></div>
                <div><span class="text-gray-500 block">Arrival</span><span class="font-medium">{{ $ticket->arrivalDate }} {{ $ticket->arrivalTime }}</span></div>
                <div><span class="text-gray-500 block">From</span><span class="font-medium">{{ $ticket->departureAirport }}</span></div>
                <div><span class="text-gray-500 block">To</span><span class="font-medium">{{ $ticket->arrivalAirport }}</span></div>
            </div>
            @endif

            @can('update', $departure)
            <form action="{{ route('departure.update-ticket', $departure) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Airline <span class="text-red-500">*</span></label>
                        <input type="text" name="airline"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->airline }}" placeholder="e.g., PIA" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Flight Number <span class="text-red-500">*</span></label>
                        <input type="text" name="flight_number"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->flightNumber }}" placeholder="e.g., PK-723" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ticket Number</label>
                        <input type="text" name="ticket_number"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->ticketNumber }}" placeholder="Optional">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PNR</label>
                        <input type="text" name="pnr"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->pnr }}" placeholder="Optional">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Departure Date <span class="text-red-500">*</span></label>
                        <input type="date" name="departure_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->departureDate }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Departure Time <span class="text-red-500">*</span></label>
                        <input type="time" name="departure_time"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->departureTime }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Arrival Date <span class="text-red-500">*</span></label>
                        <input type="date" name="arrival_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->arrivalDate }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Arrival Time <span class="text-red-500">*</span></label>
                        <input type="time" name="arrival_time"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->arrivalTime }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Departure Airport <span class="text-red-500">*</span></label>
                        <input type="text" name="departure_airport"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->departureAirport }}" placeholder="e.g., LHE" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Arrival Airport <span class="text-red-500">*</span></label>
                        <input type="text" name="arrival_airport"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ $ticket->arrivalAirport }}" placeholder="e.g., RUH" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ticket File (PDF/Image)</label>
                        <input type="file" name="ticket_file"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                               accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i> Update Ticket Details
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Briefing Card --}}
    <div class="bg-white rounded-xl shadow-sm border {{ $checklist['items']['briefing']['complete'] ? 'border-green-300' : 'border-yellow-300' }}">
        <div class="px-5 py-3 border-b {{ $checklist['items']['briefing']['complete'] ? 'bg-green-50' : 'bg-yellow-50' }} flex justify-between items-center rounded-t-xl">
            <span class="font-semibold text-gray-800"><i class="fas fa-chalkboard-teacher mr-2"></i>Pre-Departure Briefing</span>
            @if($departure->briefing_status)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorMap[$departure->briefing_status->color()] ?? 'bg-gray-100 text-gray-700' }}">
                    <i class="{{ $departure->briefing_status->icon() }} mr-1"></i>
                    {{ $departure->briefing_status->label() }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                    Not Scheduled
                </span>
            @endif
        </div>
        <div class="p-5">
            @php $briefing = $checklist['items']['briefing']['details']; @endphp
            @if($briefing->scheduledDate || $briefing->completedDate)
            <div class="space-y-1 mb-4 text-sm">
                @if($briefing->scheduledDate)
                <p><span class="font-medium text-gray-700">Scheduled Date:</span>
                    <span class="ml-1 text-gray-600">{{ $briefing->scheduledDate }}</span>
                </p>
                @endif
                @if($briefing->completedDate)
                <p><span class="font-medium text-gray-700">Completed Date:</span>
                    <span class="ml-1 text-gray-600">{{ $briefing->completedDate }}</span>
                </p>
                <p><span class="font-medium text-gray-700">Acknowledgment Signed:</span>
                    <span class="ml-1 {{ $briefing->acknowledgmentSigned ? 'text-green-600 font-medium' : 'text-red-600' }}">
                        {{ $briefing->acknowledgmentSigned ? 'Yes' : 'No' }}
                    </span>
                </p>
                @endif
                @if($briefing->notes)
                <p><span class="font-medium text-gray-700">Notes:</span>
                    <span class="ml-1 text-gray-600">{{ $briefing->notes }}</span>
                </p>
                @endif
                @if($briefing->hasDocuments())
                <p class="text-gray-400 text-xs"><i class="fas fa-paperclip mr-1"></i>Documents/video uploaded</p>
                @endif
            </div>
            @endif

            @can('update', $departure)
            @if(!$checklist['items']['briefing']['complete'])

                {{-- Schedule Form --}}
                @if(!$briefing->scheduledDate)
                <form action="{{ route('departure.schedule-briefing', $departure) }}" method="POST" class="mb-4">
                    @csrf
                    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Briefing Date <span class="text-red-500">*</span></label>
                            <input type="date" name="briefing_date"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                   min="{{ date('Y-m-d') }}" required>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                            <i class="fas fa-calendar-plus mr-2"></i> Schedule Briefing
                        </button>
                    </div>
                </form>
                @endif

                {{-- Complete Briefing Form --}}
                <form action="{{ route('departure.complete-briefing', $departure) }}" method="POST"
                      enctype="multipart/form-data" class="space-y-4 border-t pt-4 mt-4">
                    @csrf
                    <h4 class="font-medium text-gray-700 text-sm">Mark Briefing Complete</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Briefing Document (PDF)</label>
                            <input type="file" name="briefing_document"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   accept=".pdf">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Briefing Video (MP4/MOV/AVI, max 100MB)</label>
                            <input type="file" name="briefing_video"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   accept=".mp4,.mov,.avi">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Acknowledgment Form (PDF/Image)</label>
                            <input type="file" name="acknowledgment_file"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" rows="2"
                                      class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="acknowledgment_signed" value="1"
                               id="ack_signed" required
                               class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="ack_signed" class="text-sm text-gray-700">
                            <strong>Candidate has acknowledged the pre-departure briefing</strong>
                            <span class="text-red-500 ml-0.5">*</span>
                        </label>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-check-circle mr-2"></i> Mark Briefing Complete
                    </button>
                </form>
            @endif
            @endcan
        </div>
    </div>

    {{-- Action Buttons --}}
    @can('update', $departure)
    <div class="bg-white rounded-xl shadow-sm border p-5">
        @if($checklist['can_mark_ready'] && (!$departure->departure_status || $departure->departure_status->value === 'processing'))
            <form action="{{ route('departure.mark-ready', $departure) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        onclick="return confirm('Mark this candidate as Ready to Depart?')"
                        class="inline-flex items-center px-6 py-3 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold rounded-lg text-base transition-colors">
                    <i class="fas fa-plane-departure mr-2"></i> Mark Ready to Depart
                </button>
            </form>
        @elseif($departure->departure_status && $departure->departure_status->value === 'ready_to_depart')
            <form action="{{ route('departure.record-departure-actual', $departure) }}" method="POST">
                @csrf
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><strong>Actual Departure Time:</strong></label>
                        <input type="datetime-local" name="actual_departure_time"
                               class="block px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit"
                            onclick="return confirm('Record actual departure for this candidate?')"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-base transition-colors sm:mt-5">
                        <i class="fas fa-plane mr-2"></i> Record Departure
                    </button>
                </div>
            </form>
        @elseif($departure->departure_status && $departure->departure_status->value === 'departed')
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                <div>
                    <strong>Departed</strong>
                    <span class="ml-2 text-green-700">on {{ $departure->departed_at?->format('d M Y H:i') ?? '-' }}</span>
                </div>
            </div>
        @else
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
                <div>
                    <p>Complete all checklist items above before marking ready to depart.</p>
                    <p class="text-sm text-blue-600 mt-1">
                        Remaining: {{ $checklist['total'] - $checklist['completed'] }} item(s) to complete
                    </p>
                </div>
            </div>
        @endif
    </div>
    @endcan

</div>
@endsection
