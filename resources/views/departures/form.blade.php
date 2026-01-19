@extends('layouts.admin')

@section('title', isset($departure) ? 'Edit Departure' : 'Create Departure')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ isset($departure) ? 'Edit Departure Record' : 'Create Departure Record' }}
                </h1>
                <p class="text-gray-600 mt-1">Manage departure details with enhanced WASL v3 tracking</p>
            </div>
            <a href="{{ route('admin.departures.index') }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                Back to List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Please correct the following errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($departure) ? route('admin.departures.update', $departure) : route('admin.departures.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($departure))
            @method('PUT')
        @endif

        <!-- Basic Departure Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Basic Departure Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Departure Date -->
                <div>
                    <label for="departure_date" class="block text-gray-700 font-medium mb-2">
                        Departure Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           name="departure_date"
                           id="departure_date"
                           value="{{ old('departure_date', $departure->departure_date ?? '') }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Flight Number -->
                <div>
                    <label for="flight_number" class="block text-gray-700 font-medium mb-2">
                        Flight Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="flight_number"
                           id="flight_number"
                           value="{{ old('flight_number', $departure->flight_number ?? '') }}"
                           required
                           placeholder="e.g., PK-742"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Destination -->
                <div>
                    <label for="destination" class="block text-gray-700 font-medium mb-2">
                        Destination <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="destination"
                           id="destination"
                           value="{{ old('destination', $departure->destination ?? '') }}"
                           required
                           placeholder="e.g., Riyadh, Saudi Arabia"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Airport -->
                <div>
                    <label for="airport" class="block text-gray-700 font-medium mb-2">
                        Departure Airport
                    </label>
                    <input type="text"
                           name="airport"
                           id="airport"
                           value="{{ old('airport', $departure->airport ?? '') }}"
                           placeholder="e.g., Islamabad International Airport"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- PTN Status Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">PTN (Pakistan To Network) Status</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- PTN Status -->
                <div>
                    <label for="ptn_status" class="block text-gray-700 font-medium mb-2">
                        PTN Status <span class="text-red-500">*</span>
                    </label>
                    <select name="ptn_status"
                            id="ptn_status"
                            required
                            onchange="togglePTNFields(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Enums\PTNStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                    {{ old('ptn_status', $departure->ptn_status ?? 'not_applied') === $status->value ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Current PTN application status</p>
                </div>

                <!-- PTN Issued At -->
                <div id="ptn_issued_field">
                    <label for="ptn_issued_at" class="block text-gray-700 font-medium mb-2">
                        PTN Issued Date & Time
                    </label>
                    <input type="datetime-local"
                           name="ptn_issued_at"
                           id="ptn_issued_at"
                           value="{{ old('ptn_issued_at', $departure->ptn_issued_at ? $departure->ptn_issued_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- PTN Deferred Reason -->
                <div id="ptn_deferred_field" class="md:col-span-2">
                    <label for="ptn_deferred_reason" class="block text-gray-700 font-medium mb-2">
                        PTN Deferred/Refusal Reason
                    </label>
                    <textarea name="ptn_deferred_reason"
                              id="ptn_deferred_reason"
                              rows="3"
                              placeholder="Enter reason if PTN was not issued or refused"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('ptn_deferred_reason', $departure->ptn_deferred_reason ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Protector Status Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Protector Status</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Protector Status -->
                <div>
                    <label for="protector_status" class="block text-gray-700 font-medium mb-2">
                        Protector Status <span class="text-red-500">*</span>
                    </label>
                    <select name="protector_status"
                            id="protector_status"
                            required
                            onchange="toggleProtectorFields(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Enums\ProtectorStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                    {{ old('protector_status', $departure->protector_status ?? 'not_applied') === $status->value ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Protector application status</p>
                </div>

                <!-- Protector Applied At -->
                <div id="protector_applied_field">
                    <label for="protector_applied_at" class="block text-gray-700 font-medium mb-2">
                        Protector Applied Date & Time
                    </label>
                    <input type="datetime-local"
                           name="protector_applied_at"
                           id="protector_applied_at"
                           value="{{ old('protector_applied_at', $departure->protector_applied_at ? $departure->protector_applied_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Protector Done At -->
                <div id="protector_done_field">
                    <label for="protector_done_at" class="block text-gray-700 font-medium mb-2">
                        Protector Done Date & Time
                    </label>
                    <input type="datetime-local"
                           name="protector_done_at"
                           id="protector_done_at"
                           value="{{ old('protector_done_at', $departure->protector_done_at ? $departure->protector_done_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Protector Deferred Reason -->
                <div id="protector_deferred_field" class="md:col-span-2">
                    <label for="protector_deferred_reason" class="block text-gray-700 font-medium mb-2">
                        Protector Deferred/Refusal Reason
                    </label>
                    <textarea name="protector_deferred_reason"
                              id="protector_deferred_reason"
                              rows="3"
                              placeholder="Enter reason if Protector was not issued or refused"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('protector_deferred_reason', $departure->protector_deferred_reason ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Ticket Details Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Ticket Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ticket Date -->
                <div>
                    <label for="ticket_date" class="block text-gray-700 font-medium mb-2">
                        Ticket Date
                    </label>
                    <input type="date"
                           name="ticket_date"
                           id="ticket_date"
                           value="{{ old('ticket_date', $departure->ticket_date ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Ticket Time -->
                <div>
                    <label for="ticket_time" class="block text-gray-700 font-medium mb-2">
                        Ticket Time
                    </label>
                    <input type="time"
                           name="ticket_time"
                           id="ticket_time"
                           value="{{ old('ticket_time', $departure->ticket_time ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Departure Platform -->
                <div>
                    <label for="departure_platform" class="block text-gray-700 font-medium mb-2">
                        Departure Platform
                    </label>
                    <input type="text"
                           name="departure_platform"
                           id="departure_platform"
                           value="{{ old('departure_platform', $departure->departure_platform ?? '') }}"
                           placeholder="e.g., Terminal 1, Gate 5"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Landing Platform -->
                <div>
                    <label for="landing_platform" class="block text-gray-700 font-medium mb-2">
                        Landing Platform
                    </label>
                    <input type="text"
                           name="landing_platform"
                           id="landing_platform"
                           value="{{ old('landing_platform', $departure->landing_platform ?? '') }}"
                           placeholder="e.g., Terminal 3, Riyadh"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Flight Type -->
                <div>
                    <label for="flight_type" class="block text-gray-700 font-medium mb-2">
                        Flight Type
                    </label>
                    <select name="flight_type"
                            id="flight_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Flight Type --</option>
                        @foreach(\App\Enums\FlightType::cases() as $type)
                            <option value="{{ $type->value }}"
                                    {{ old('flight_type', $departure->flight_type ?? '') === $type->value ? 'selected' : '' }}>
                                {{ ucfirst($type->value) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Direct or Connected flight</p>
                </div>
            </div>
        </div>

        <!-- Pre-Departure Briefing Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Pre-Departure Briefing</h2>

            @if(isset($departure))
                <!-- Current Document Display -->
                @if($departure->pre_departure_doc_path)
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800 mb-2">
                            <strong>Current Briefing Document:</strong> {{ basename($departure->pre_departure_doc_path) }}
                        </p>
                        <a href="{{ route('admin.departures.download-briefing-doc', $departure) }}"
                           class="text-blue-600 hover:text-blue-800 text-sm underline">
                            Download Current Document
                        </a>
                    </div>
                @endif

                <!-- Current Video Display -->
                @if($departure->pre_departure_video_path)
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800 mb-2">
                            <strong>Current Briefing Video:</strong> {{ basename($departure->pre_departure_video_path) }}
                        </p>
                        <a href="{{ route('admin.departures.download-briefing-video', $departure) }}"
                           class="text-blue-600 hover:text-blue-800 text-sm underline">
                            Download Current Video
                        </a>
                    </div>
                @endif
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Briefing Document Upload -->
                <div>
                    <label for="pre_departure_doc" class="block text-gray-700 font-medium mb-2">
                        Briefing Document (Scanned Original Documents)
                    </label>
                    <input type="file"
                           name="pre_departure_doc"
                           id="pre_departure_doc"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Upload scanned original documents given to candidate
                        <br>Allowed: PDF, JPG, PNG (Max 10MB)
                    </p>
                </div>

                <!-- Briefing Video Upload -->
                <div>
                    <label for="pre_departure_video" class="block text-gray-700 font-medium mb-2">
                        Briefing Video
                    </label>
                    <input type="file"
                           name="pre_departure_video"
                           id="pre_departure_video"
                           accept=".mp4,.avi,.mov"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Upload video of candidate receiving documents, T&Cs, success story
                        <br>Allowed: MP4, AVI, MOV (Max 100MB)
                    </p>
                </div>

                <!-- Briefing Date -->
                <div>
                    <label for="briefing_date" class="block text-gray-700 font-medium mb-2">
                        Briefing Date
                    </label>
                    <input type="date"
                           name="briefing_date"
                           id="briefing_date"
                           value="{{ old('briefing_date', $departure->briefing_date ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Briefing Completed -->
                <div>
                    <label for="briefing_completed" class="block text-gray-700 font-medium mb-2">
                        Briefing Status
                    </label>
                    <select name="briefing_completed"
                            id="briefing_completed"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('briefing_completed', $departure->briefing_completed ?? 0) == 0 ? 'selected' : '' }}>Not Completed</option>
                        <option value="1" {{ old('briefing_completed', $departure->briefing_completed ?? 0) == 1 ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>

            <!-- Briefing Remarks -->
            <div class="mt-4">
                <label for="briefing_remarks" class="block text-gray-700 font-medium mb-2">
                    Briefing Remarks
                </label>
                <textarea name="briefing_remarks"
                          id="briefing_remarks"
                          rows="3"
                          placeholder="Any additional notes about the pre-departure briefing"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('briefing_remarks', $departure->briefing_remarks ?? '') }}</textarea>
            </div>
        </div>

        <!-- Final Departure Status -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Final Departure Status</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Final Departure Status -->
                <div>
                    <label for="final_departure_status" class="block text-gray-700 font-medium mb-2">
                        Final Status <span class="text-red-500">*</span>
                    </label>
                    <select name="final_departure_status"
                            id="final_departure_status"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Enums\DepartureStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                    {{ old('final_departure_status', $departure->final_departure_status ?? 'processing') === $status->value ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Overall departure readiness status</p>
                </div>

                <!-- Ready for Departure -->
                <div>
                    <label for="ready_for_departure" class="block text-gray-700 font-medium mb-2">
                        Ready for Departure
                    </label>
                    <select name="ready_for_departure"
                            id="ready_for_departure"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('ready_for_departure', $departure->ready_for_departure ?? 0) == 0 ? 'selected' : '' }}>Not Ready</option>
                        <option value="1" {{ old('ready_for_departure', $departure->ready_for_departure ?? 0) == 1 ? 'selected' : '' }}>Ready</option>
                    </select>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mt-4">
                <label for="remarks" class="block text-gray-700 font-medium mb-2">
                    Additional Remarks
                </label>
                <textarea name="remarks"
                          id="remarks"
                          rows="3"
                          placeholder="Any additional notes about the departure"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('remarks', $departure->remarks ?? '') }}</textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center bg-white rounded-lg shadow-md p-6">
            <a href="{{ route('admin.departures.index') }}"
               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                Cancel
            </a>

            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                {{ isset($departure) ? 'Update Departure' : 'Save Departure' }}
            </button>
        </div>
    </form>
</div>

<!-- JavaScript for conditional field display -->
<script>
    // PTN Status conditional fields
    function togglePTNFields(status) {
        const issuedField = document.getElementById('ptn_issued_field');
        const deferredField = document.getElementById('ptn_deferred_field');

        if (status === 'issued' || status === 'done') {
            issuedField.style.display = 'block';
            deferredField.style.display = 'none';
        } else if (status === 'not_issued' || status === 'refused') {
            issuedField.style.display = 'none';
            deferredField.style.display = 'block';
        } else {
            issuedField.style.display = 'none';
            deferredField.style.display = 'none';
        }
    }

    // Protector Status conditional fields
    function toggleProtectorFields(status) {
        const appliedField = document.getElementById('protector_applied_field');
        const doneField = document.getElementById('protector_done_field');
        const deferredField = document.getElementById('protector_deferred_field');

        if (status === 'applied' || status === 'pending') {
            appliedField.style.display = 'block';
            doneField.style.display = 'none';
            deferredField.style.display = 'none';
        } else if (status === 'done') {
            appliedField.style.display = 'block';
            doneField.style.display = 'block';
            deferredField.style.display = 'none';
        } else if (status === 'not_issued' || status === 'refused') {
            appliedField.style.display = 'none';
            doneField.style.display = 'none';
            deferredField.style.display = 'block';
        } else {
            appliedField.style.display = 'none';
            doneField.style.display = 'none';
            deferredField.style.display = 'none';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const ptnStatus = document.getElementById('ptn_status').value;
        const protectorStatus = document.getElementById('protector_status').value;

        togglePTNFields(ptnStatus);
        toggleProtectorFields(protectorStatus);
    });
</script>
@endsection
