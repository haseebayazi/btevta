# Module 6: Departure Enhancement - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 6 - Departure (Enhancement)
**Status:** Existing Module - Requires Modifications
**Date:** February 2026

---

## Executive Summary

Module 6 (Departure) **ALREADY EXISTS** with comprehensive functionality:
- 25+ controller methods
- 27 service methods
- 90-day compliance tracking with checklist
- Issue tracking with UUID-based IDs
- Welfare monitoring dashboard
- Compliance analytics and reporting

This prompt focuses on **ENHANCEMENTS** for structured status tracking (PTN, Protector, Ticket), pre-departure briefing with media uploads, and enhanced departure dashboard.

**CRITICAL:** This is a working system. Modifications should be surgical and additive.

---

## Pre-Implementation Analysis

### Step 1: Read Existing Implementation

```
# Controllers (25+ methods)
app/Http/Controllers/DepartureController.php

# Services (27 methods)
app/Services/DepartureService.php

# Models
app/Models/Departure.php
app/Models/PostDepartureDetail.php

# Views
resources/views/departure/

# Tests
tests/Feature/DepartureControllerTest.php
tests/Unit/DepartureServiceTest.php
```

### Step 2: Understand Current Schema

```bash
php artisan tinker --execute="Schema::getColumnListing('departures')"
```

---

## Required Changes (from WASL_CHANGE_IMPACT_ANALYSIS.md)

| Change ID | Type | Description | Priority |
|-----------|------|-------------|----------|
| DP-001 | MODIFIED | PTN Status restructured with sub-details | HIGH |
| DP-002 | NEW | Protector Status: Applied/Done/Pending/Deferred | HIGH |
| DP-003 | MODIFIED | Ticket Status with sub-details (airline, flight, date) | HIGH |
| DP-004 | NEW | Pre-Departure Briefing with document upload | HIGH |
| DP-005 | NEW | Pre-Departure Briefing video upload | MEDIUM |
| DP-006 | MODIFIED | Final Departure Status: Ready to Depart / Departed | HIGH |
| DP-007 | NEW | Enhanced Departure Dashboard | HIGH |

---

## Phase 1: Database Changes

### 1.1 Add Enhanced Status Columns

```php
// database/migrations/YYYY_MM_DD_enhance_departures_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            // PTN Status Enhancement
            if (!Schema::hasColumn('departures', 'ptn_details')) {
                $table->json('ptn_details')->nullable()->after('ptn_number');
                // Contains: status, issued_date, expiry_date, evidence_path
            }

            // Protector Status (new)
            if (!Schema::hasColumn('departures', 'protector_status')) {
                $table->enum('protector_status', ['not_started', 'applied', 'done', 'pending', 'deferred'])
                    ->default('not_started')
                    ->after('ptn_details');
            }
            if (!Schema::hasColumn('departures', 'protector_details')) {
                $table->json('protector_details')->nullable()->after('protector_status');
                // Contains: applied_date, completion_date, certificate_path, notes
            }

            // Ticket Status Enhancement
            if (!Schema::hasColumn('departures', 'ticket_details')) {
                $table->json('ticket_details')->nullable()->after('ticket_path');
                // Contains: airline, flight_number, departure_date, departure_time,
                //           arrival_date, arrival_time, departure_airport, arrival_airport
            }

            // Pre-Departure Briefing Enhancement
            if (!Schema::hasColumn('departures', 'briefing_status')) {
                $table->enum('briefing_status', ['not_scheduled', 'scheduled', 'completed'])
                    ->default('not_scheduled')
                    ->after('ticket_details');
            }
            if (!Schema::hasColumn('departures', 'briefing_details')) {
                $table->json('briefing_details')->nullable()->after('briefing_status');
                // Contains: scheduled_date, completed_date, document_path, video_path,
                //           acknowledgment_signed, acknowledgment_path
            }

            // Final Departure Status
            if (!Schema::hasColumn('departures', 'departure_status')) {
                $table->enum('departure_status', ['processing', 'ready_to_depart', 'departed', 'cancelled'])
                    ->default('processing')
                    ->after('briefing_details');
            }
            if (!Schema::hasColumn('departures', 'departed_at')) {
                $table->timestamp('departed_at')->nullable()->after('departure_status');
            }

            // Indexes
            $table->index('protector_status');
            $table->index('briefing_status');
            $table->index('departure_status');
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn([
                'ptn_details', 'protector_status', 'protector_details',
                'ticket_details', 'briefing_status', 'briefing_details',
                'departure_status', 'departed_at',
            ]);
        });
    }
};
```

---

## Phase 2: Create Enums

### 2.1 ProtectorStatus Enum

```php
// app/Enums/ProtectorStatus.php
<?php

namespace App\Enums;

enum ProtectorStatus: string
{
    case NOT_STARTED = 'not_started';
    case APPLIED = 'applied';
    case DONE = 'done';
    case PENDING = 'pending';
    case DEFERRED = 'deferred';

    public function label(): string
    {
        return match($this) {
            self::NOT_STARTED => 'Not Started',
            self::APPLIED => 'Applied',
            self::DONE => 'Completed',
            self::PENDING => 'Pending',
            self::DEFERRED => 'Deferred',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NOT_STARTED => 'secondary',
            self::APPLIED => 'info',
            self::DONE => 'success',
            self::PENDING => 'warning',
            self::DEFERRED => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::NOT_STARTED => 'fas fa-circle',
            self::APPLIED => 'fas fa-paper-plane',
            self::DONE => 'fas fa-check-circle',
            self::PENDING => 'fas fa-clock',
            self::DEFERRED => 'fas fa-pause-circle',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

### 2.2 BriefingStatus Enum

```php
// app/Enums/BriefingStatus.php
<?php

namespace App\Enums;

enum BriefingStatus: string
{
    case NOT_SCHEDULED = 'not_scheduled';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::NOT_SCHEDULED => 'Not Scheduled',
            self::SCHEDULED => 'Scheduled',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NOT_SCHEDULED => 'secondary',
            self::SCHEDULED => 'info',
            self::COMPLETED => 'success',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

### 2.3 DepartureStatus Enum

```php
// app/Enums/DepartureStatus.php
<?php

namespace App\Enums;

enum DepartureStatus: string
{
    case PROCESSING = 'processing';
    case READY_TO_DEPART = 'ready_to_depart';
    case DEPARTED = 'departed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PROCESSING => 'Processing',
            self::READY_TO_DEPART => 'Ready to Depart',
            self::DEPARTED => 'Departed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PROCESSING => 'warning',
            self::READY_TO_DEPART => 'info',
            self::DEPARTED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PROCESSING => 'fas fa-cog fa-spin',
            self::READY_TO_DEPART => 'fas fa-plane-departure',
            self::DEPARTED => 'fas fa-plane',
            self::CANCELLED => 'fas fa-times-circle',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

---

## Phase 3: Create Value Objects

### 3.1 PTNDetails Value Object

```php
// app/ValueObjects/PTNDetails.php
<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class PTNDetails implements Arrayable
{
    public function __construct(
        public ?string $status = null,
        public ?string $issuedDate = null,
        public ?string $expiryDate = null,
        public ?string $evidencePath = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) return new self();

        return new self(
            status: $data['status'] ?? null,
            issuedDate: $data['issued_date'] ?? null,
            expiryDate: $data['expiry_date'] ?? null,
            evidencePath: $data['evidence_path'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'issued_date' => $this->issuedDate,
            'expiry_date' => $this->expiryDate,
            'evidence_path' => $this->evidencePath,
            'notes' => $this->notes,
        ], fn($v) => $v !== null);
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued' && $this->issuedDate !== null;
    }

    public function isExpired(): bool
    {
        if (!$this->expiryDate) return false;
        return now()->greaterThan($this->expiryDate);
    }
}
```

### 3.2 TicketDetails Value Object

```php
// app/ValueObjects/TicketDetails.php
<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class TicketDetails implements Arrayable
{
    public function __construct(
        public ?string $airline = null,
        public ?string $flightNumber = null,
        public ?string $departureDate = null,
        public ?string $departureTime = null,
        public ?string $arrivalDate = null,
        public ?string $arrivalTime = null,
        public ?string $departureAirport = null,
        public ?string $arrivalAirport = null,
        public ?string $ticketNumber = null,
        public ?string $ticketPath = null,
        public ?string $pnr = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) return new self();

        return new self(
            airline: $data['airline'] ?? null,
            flightNumber: $data['flight_number'] ?? null,
            departureDate: $data['departure_date'] ?? null,
            departureTime: $data['departure_time'] ?? null,
            arrivalDate: $data['arrival_date'] ?? null,
            arrivalTime: $data['arrival_time'] ?? null,
            departureAirport: $data['departure_airport'] ?? null,
            arrivalAirport: $data['arrival_airport'] ?? null,
            ticketNumber: $data['ticket_number'] ?? null,
            ticketPath: $data['ticket_path'] ?? null,
            pnr: $data['pnr'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'airline' => $this->airline,
            'flight_number' => $this->flightNumber,
            'departure_date' => $this->departureDate,
            'departure_time' => $this->departureTime,
            'arrival_date' => $this->arrivalDate,
            'arrival_time' => $this->arrivalTime,
            'departure_airport' => $this->departureAirport,
            'arrival_airport' => $this->arrivalAirport,
            'ticket_number' => $this->ticketNumber,
            'ticket_path' => $this->ticketPath,
            'pnr' => $this->pnr,
        ], fn($v) => $v !== null);
    }

    public function isComplete(): bool
    {
        return $this->airline && $this->flightNumber && $this->departureDate;
    }

    public function getDepartureDateTime(): ?\Carbon\Carbon
    {
        if (!$this->departureDate) return null;
        $dateTime = $this->departureDate;
        if ($this->departureTime) {
            $dateTime .= ' ' . $this->departureTime;
        }
        return \Carbon\Carbon::parse($dateTime);
    }
}
```

### 3.3 BriefingDetails Value Object

```php
// app/ValueObjects/BriefingDetails.php
<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class BriefingDetails implements Arrayable
{
    public function __construct(
        public ?string $scheduledDate = null,
        public ?string $completedDate = null,
        public ?string $documentPath = null,
        public ?string $videoPath = null,
        public bool $acknowledgmentSigned = false,
        public ?string $acknowledgmentPath = null,
        public ?string $notes = null,
        public ?int $conductedBy = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) return new self();

        return new self(
            scheduledDate: $data['scheduled_date'] ?? null,
            completedDate: $data['completed_date'] ?? null,
            documentPath: $data['document_path'] ?? null,
            videoPath: $data['video_path'] ?? null,
            acknowledgmentSigned: $data['acknowledgment_signed'] ?? false,
            acknowledgmentPath: $data['acknowledgment_path'] ?? null,
            notes: $data['notes'] ?? null,
            conductedBy: $data['conducted_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'scheduled_date' => $this->scheduledDate,
            'completed_date' => $this->completedDate,
            'document_path' => $this->documentPath,
            'video_path' => $this->videoPath,
            'acknowledgment_signed' => $this->acknowledgmentSigned,
            'acknowledgment_path' => $this->acknowledgmentPath,
            'notes' => $this->notes,
            'conducted_by' => $this->conductedBy,
        ], fn($v) => $v !== null && $v !== false);
    }

    public function isComplete(): bool
    {
        return $this->completedDate !== null && $this->acknowledgmentSigned;
    }

    public function hasDocuments(): bool
    {
        return $this->documentPath !== null || $this->videoPath !== null;
    }
}
```

---

## Phase 4: Update Departure Model

Add to `app/Models/Departure.php`:

```php
use App\Enums\ProtectorStatus;
use App\Enums\BriefingStatus;
use App\Enums\DepartureStatus;
use App\ValueObjects\PTNDetails;
use App\ValueObjects\TicketDetails;
use App\ValueObjects\BriefingDetails;

// Add to $fillable:
'ptn_details',
'protector_status',
'protector_details',
'ticket_details',
'briefing_status',
'briefing_details',
'departure_status',
'departed_at',

// Add to $casts:
'ptn_details' => 'array',
'protector_details' => 'array',
'ticket_details' => 'array',
'briefing_details' => 'array',
'protector_status' => ProtectorStatus::class,
'briefing_status' => BriefingStatus::class,
'departure_status' => DepartureStatus::class,
'departed_at' => 'datetime',

// Add accessors:

public function getPtnDetailsObjectAttribute(): PTNDetails
{
    return PTNDetails::fromArray($this->ptn_details);
}

public function getTicketDetailsObjectAttribute(): TicketDetails
{
    return TicketDetails::fromArray($this->ticket_details);
}

public function getBriefingDetailsObjectAttribute(): BriefingDetails
{
    return BriefingDetails::fromArray($this->briefing_details);
}

/**
 * Check if ready to depart
 */
public function canMarkReadyToDepart(): bool
{
    return $this->ptn_details_object->isIssued()
        && $this->protector_status === ProtectorStatus::DONE
        && $this->ticket_details_object->isComplete()
        && $this->briefing_details_object->isComplete();
}

/**
 * Get departure checklist status
 */
public function getDepartureChecklist(): array
{
    return [
        'ptn' => [
            'label' => 'PTN Issued',
            'complete' => $this->ptn_details_object->isIssued(),
            'details' => $this->ptn_details_object,
        ],
        'protector' => [
            'label' => 'Protector Clearance',
            'complete' => $this->protector_status === ProtectorStatus::DONE,
            'status' => $this->protector_status,
        ],
        'ticket' => [
            'label' => 'Flight Ticket',
            'complete' => $this->ticket_details_object->isComplete(),
            'details' => $this->ticket_details_object,
        ],
        'briefing' => [
            'label' => 'Pre-Departure Briefing',
            'complete' => $this->briefing_details_object->isComplete(),
            'details' => $this->briefing_details_object,
        ],
    ];
}

/**
 * Update PTN details
 */
public function updatePTN(string $ptnNumber, string $issuedDate, ?string $expiryDate = null, $evidenceFile = null): void
{
    $evidencePath = $this->ptn_details_object->evidencePath;

    if ($evidenceFile) {
        $evidencePath = $this->uploadFile($evidenceFile, 'ptn');
    }

    $this->ptn_number = $ptnNumber;
    $this->ptn_details = (new PTNDetails(
        status: 'issued',
        issuedDate: $issuedDate,
        expiryDate: $expiryDate,
        evidencePath: $evidencePath,
    ))->toArray();
    $this->save();

    $this->logActivity('PTN issued', ['ptn_number' => $ptnNumber]);
}

/**
 * Update protector status
 */
public function updateProtectorStatus(string $status, ?array $details = null, $certificateFile = null): void
{
    $this->protector_status = ProtectorStatus::from($status);

    $existingDetails = $this->protector_details ?? [];
    $newDetails = array_merge($existingDetails, $details ?? []);

    if ($certificateFile) {
        $newDetails['certificate_path'] = $this->uploadFile($certificateFile, 'protector');
    }

    if ($status === 'done') {
        $newDetails['completion_date'] = now()->toDateString();
    } elseif ($status === 'applied') {
        $newDetails['applied_date'] = now()->toDateString();
    }

    $this->protector_details = $newDetails;
    $this->save();

    $this->logActivity('Protector status updated', ['status' => $status]);
}

/**
 * Update ticket details
 */
public function updateTicketDetails(array $ticketData, $ticketFile = null): void
{
    $ticketPath = $ticketFile ? $this->uploadFile($ticketFile, 'ticket') : null;

    $this->ticket_details = (new TicketDetails(
        airline: $ticketData['airline'] ?? null,
        flightNumber: $ticketData['flight_number'] ?? null,
        departureDate: $ticketData['departure_date'] ?? null,
        departureTime: $ticketData['departure_time'] ?? null,
        arrivalDate: $ticketData['arrival_date'] ?? null,
        arrivalTime: $ticketData['arrival_time'] ?? null,
        departureAirport: $ticketData['departure_airport'] ?? null,
        arrivalAirport: $ticketData['arrival_airport'] ?? null,
        ticketNumber: $ticketData['ticket_number'] ?? null,
        ticketPath: $ticketPath ?? $this->ticket_details_object->ticketPath,
        pnr: $ticketData['pnr'] ?? null,
    ))->toArray();

    if ($ticketPath) {
        $this->ticket_path = $ticketPath;
    }

    $this->save();

    $this->logActivity('Ticket details updated', [
        'airline' => $ticketData['airline'] ?? null,
        'flight' => $ticketData['flight_number'] ?? null,
    ]);
}

/**
 * Schedule pre-departure briefing
 */
public function scheduleBriefing(string $date): void
{
    $details = $this->briefing_details ?? [];
    $details['scheduled_date'] = $date;

    $this->briefing_status = BriefingStatus::SCHEDULED;
    $this->briefing_details = $details;
    $this->save();

    $this->logActivity('Briefing scheduled', ['date' => $date]);
}

/**
 * Complete pre-departure briefing
 */
public function completeBriefing(
    bool $acknowledgmentSigned,
    ?string $notes = null,
    $documentFile = null,
    $videoFile = null,
    $acknowledgmentFile = null
): void {
    $details = BriefingDetails::fromArray($this->briefing_details);

    $documentPath = $documentFile ? $this->uploadFile($documentFile, 'briefing/documents') : $details->documentPath;
    $videoPath = $videoFile ? $this->uploadFile($videoFile, 'briefing/videos') : $details->videoPath;
    $ackPath = $acknowledgmentFile ? $this->uploadFile($acknowledgmentFile, 'briefing/acknowledgments') : $details->acknowledgmentPath;

    $this->briefing_status = BriefingStatus::COMPLETED;
    $this->briefing_details = (new BriefingDetails(
        scheduledDate: $details->scheduledDate,
        completedDate: now()->toDateString(),
        documentPath: $documentPath,
        videoPath: $videoPath,
        acknowledgmentSigned: $acknowledgmentSigned,
        acknowledgmentPath: $ackPath,
        notes: $notes,
        conductedBy: auth()->id(),
    ))->toArray();
    $this->save();

    $this->logActivity('Briefing completed', ['acknowledgment_signed' => $acknowledgmentSigned]);
}

/**
 * Mark as ready to depart
 */
public function markReadyToDepart(): void
{
    if (!$this->canMarkReadyToDepart()) {
        throw new \Exception('All departure requirements must be complete before marking ready to depart.');
    }

    $this->departure_status = DepartureStatus::READY_TO_DEPART;
    $this->save();

    $this->candidate->update(['status' => 'ready_to_depart']);

    $this->logActivity('Marked ready to depart');
}

/**
 * Record departure
 */
public function recordDeparture(?string $actualDepartureTime = null): void
{
    $this->departure_status = DepartureStatus::DEPARTED;
    $this->departed_at = $actualDepartureTime ? \Carbon\Carbon::parse($actualDepartureTime) : now();
    $this->save();

    $this->candidate->update(['status' => 'departed']);

    $this->logActivity('Departed', ['departed_at' => $this->departed_at]);
}

/**
 * Helper: Upload file
 */
protected function uploadFile($file, string $subfolder): string
{
    $candidateId = $this->candidate_id;
    $timestamp = now()->format('Y-m-d_His');
    $extension = $file->getClientOriginalExtension();
    $filename = "{$subfolder}_{$candidateId}_{$timestamp}.{$extension}";

    return $file->storeAs(
        "departures/{$candidateId}/{$subfolder}",
        $filename,
        'private'
    );
}

/**
 * Helper: Log activity
 */
protected function logActivity(string $message, array $properties = []): void
{
    activity()
        ->performedOn($this)
        ->causedBy(auth()->user())
        ->withProperties($properties)
        ->log($message);
}
```

---

## Phase 5: Create Form Requests

### 5.1 UpdateTicketDetailsRequest

```php
// app/Http/Requests/UpdateTicketDetailsRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline' => 'required|string|max:100',
            'flight_number' => 'required|string|max:20',
            'departure_date' => 'required|date|after_or_equal:today',
            'departure_time' => 'required|date_format:H:i',
            'arrival_date' => 'required|date|after_or_equal:departure_date',
            'arrival_time' => 'required|date_format:H:i',
            'departure_airport' => 'required|string|max:100',
            'arrival_airport' => 'required|string|max:100',
            'ticket_number' => 'nullable|string|max:50',
            'pnr' => 'nullable|string|max:20',
            'ticket_file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ];
    }
}
```

### 5.2 CompleteBriefingRequest

```php
// app/Http/Requests/CompleteBriefingRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteBriefingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acknowledgment_signed' => 'required|boolean|accepted',
            'notes' => 'nullable|string|max:2000',
            'briefing_document' => 'nullable|file|max:10240|mimes:pdf',
            'briefing_video' => 'nullable|file|max:102400|mimes:mp4,mov,avi', // 100MB max
            'acknowledgment_file' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ];
    }

    public function messages(): array
    {
        return [
            'acknowledgment_signed.accepted' => 'Candidate must acknowledge the pre-departure briefing.',
            'briefing_video.max' => 'Video file must be less than 100MB.',
        ];
    }
}
```

---

## Phase 6: Update Departure Service

Add to `app/Services/DepartureService.php`:

```php
use App\Enums\ProtectorStatus;
use App\Enums\BriefingStatus;
use App\Enums\DepartureStatus;

/**
 * Get enhanced departure dashboard data
 */
public function getEnhancedDashboard(?int $campusId = null): array
{
    $query = Departure::with(['candidate.campus', 'candidate.trade'])
        ->whereHas('candidate');

    if ($campusId) {
        $query->whereHas('candidate', fn($q) => $q->where('campus_id', $campusId));
    }

    $departures = $query->get();

    return [
        'summary' => [
            'total' => $departures->count(),
            'processing' => $departures->where('departure_status', DepartureStatus::PROCESSING)->count(),
            'ready_to_depart' => $departures->where('departure_status', DepartureStatus::READY_TO_DEPART)->count(),
            'departed' => $departures->where('departure_status', DepartureStatus::DEPARTED)->count(),
        ],
        'ptn_status' => [
            'pending' => $departures->filter(fn($d) => !$d->ptn_details_object->isIssued())->count(),
            'issued' => $departures->filter(fn($d) => $d->ptn_details_object->isIssued())->count(),
        ],
        'protector_status' => [
            'not_started' => $departures->where('protector_status', ProtectorStatus::NOT_STARTED)->count(),
            'applied' => $departures->where('protector_status', ProtectorStatus::APPLIED)->count(),
            'done' => $departures->where('protector_status', ProtectorStatus::DONE)->count(),
            'pending' => $departures->where('protector_status', ProtectorStatus::PENDING)->count(),
            'deferred' => $departures->where('protector_status', ProtectorStatus::DEFERRED)->count(),
        ],
        'briefing_status' => [
            'not_scheduled' => $departures->where('briefing_status', BriefingStatus::NOT_SCHEDULED)->count(),
            'scheduled' => $departures->where('briefing_status', BriefingStatus::SCHEDULED)->count(),
            'completed' => $departures->where('briefing_status', BriefingStatus::COMPLETED)->count(),
        ],
        'upcoming_flights' => $departures
            ->filter(fn($d) => $d->ticket_details_object->departureDate !== null)
            ->filter(fn($d) => $d->ticket_details_object->getDepartureDateTime()?->isFuture())
            ->sortBy(fn($d) => $d->ticket_details_object->getDepartureDateTime())
            ->take(10)
            ->values(),
        'ready_candidates' => $departures
            ->where('departure_status', DepartureStatus::READY_TO_DEPART)
            ->values(),
    ];
}

/**
 * Get departure checklist status for candidate
 */
public function getDepartureChecklist(Departure $departure): array
{
    $checklist = $departure->getDepartureChecklist();

    $completedCount = collect($checklist)->filter(fn($item) => $item['complete'])->count();
    $totalCount = count($checklist);

    return [
        'items' => $checklist,
        'completed' => $completedCount,
        'total' => $totalCount,
        'percentage' => $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0,
        'can_mark_ready' => $departure->canMarkReadyToDepart(),
    ];
}

/**
 * Process PTN issuance
 */
public function issuePTN(
    Departure $departure,
    string $ptnNumber,
    string $issuedDate,
    ?string $expiryDate = null,
    $evidenceFile = null
): void {
    DB::transaction(function () use ($departure, $ptnNumber, $issuedDate, $expiryDate, $evidenceFile) {
        $departure->updatePTN($ptnNumber, $issuedDate, $expiryDate, $evidenceFile);
    });
}

/**
 * Update protector status
 */
public function updateProtector(
    Departure $departure,
    string $status,
    ?string $notes = null,
    $certificateFile = null
): void {
    $departure->updateProtectorStatus($status, ['notes' => $notes], $certificateFile);
}

/**
 * Update ticket with full details
 */
public function updateTicket(Departure $departure, array $ticketData, $ticketFile = null): void
{
    $departure->updateTicketDetails($ticketData, $ticketFile);
}

/**
 * Schedule briefing
 */
public function scheduleBriefing(Departure $departure, string $date): void
{
    $departure->scheduleBriefing($date);

    // Send notification to candidate
    // $departure->candidate->notify(new BriefingScheduledNotification($date));
}

/**
 * Complete briefing with uploads
 */
public function completeBriefing(
    Departure $departure,
    bool $acknowledgmentSigned,
    ?string $notes = null,
    $documentFile = null,
    $videoFile = null,
    $acknowledgmentFile = null
): void {
    $departure->completeBriefing(
        $acknowledgmentSigned,
        $notes,
        $documentFile,
        $videoFile,
        $acknowledgmentFile
    );
}

/**
 * Process ready to depart
 */
public function markReadyToDepart(Departure $departure): void
{
    $departure->markReadyToDepart();
}

/**
 * Record actual departure
 */
public function recordDeparture(Departure $departure, ?string $actualTime = null): void
{
    $departure->recordDeparture($actualTime);

    // Trigger post-departure workflow
    event(new \App\Events\CandidateDeparted($departure->candidate, $departure));
}
```

---

## Phase 7: Update Controller

Add to `app/Http/Controllers/DepartureController.php`:

```php
/**
 * Enhanced departure dashboard
 */
public function enhancedDashboard(Request $request)
{
    $this->authorize('viewAny', Departure::class);

    $user = auth()->user();
    $campusId = $user->isCampusAdmin() ? $user->campus_id : $request->get('campus_id');

    $service = app(DepartureService::class);
    $dashboard = $service->getEnhancedDashboard($campusId);

    $campuses = Campus::active()->orderBy('name')->get();

    return view('departure.enhanced-dashboard', compact('dashboard', 'campuses'));
}

/**
 * Departure checklist view
 */
public function checklist(Departure $departure)
{
    $this->authorize('view', $departure);

    $service = app(DepartureService::class);
    $checklist = $service->getDepartureChecklist($departure);

    return view('departure.checklist', compact('departure', 'checklist'));
}

/**
 * Update PTN
 */
public function updatePTN(Request $request, Departure $departure)
{
    $this->authorize('update', $departure);

    $validated = $request->validate([
        'ptn_number' => 'required|string|max:50',
        'issued_date' => 'required|date',
        'expiry_date' => 'nullable|date|after:issued_date',
        'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
    ]);

    try {
        $service = app(DepartureService::class);
        $service->issuePTN(
            $departure,
            $validated['ptn_number'],
            $validated['issued_date'],
            $validated['expiry_date'] ?? null,
            $request->file('evidence')
        );

        return back()->with('success', 'PTN details updated successfully.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}

/**
 * Update protector status
 */
public function updateProtector(Request $request, Departure $departure)
{
    $this->authorize('update', $departure);

    $validated = $request->validate([
        'status' => 'required|in:not_started,applied,done,pending,deferred',
        'notes' => 'nullable|string|max:1000',
        'certificate' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
    ]);

    try {
        $service = app(DepartureService::class);
        $service->updateProtector(
            $departure,
            $validated['status'],
            $validated['notes'] ?? null,
            $request->file('certificate')
        );

        return back()->with('success', 'Protector status updated.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}

/**
 * Update ticket details
 */
public function updateTicket(UpdateTicketDetailsRequest $request, Departure $departure)
{
    $this->authorize('update', $departure);

    try {
        $service = app(DepartureService::class);
        $service->updateTicket($departure, $request->validated(), $request->file('ticket_file'));

        return back()->with('success', 'Ticket details updated successfully.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}

/**
 * Schedule briefing
 */
public function scheduleBriefing(Request $request, Departure $departure)
{
    $this->authorize('update', $departure);

    $validated = $request->validate([
        'briefing_date' => 'required|date|after_or_equal:today',
    ]);

    try {
        $service = app(DepartureService::class);
        $service->scheduleBriefing($departure, $validated['briefing_date']);

        return back()->with('success', 'Pre-departure briefing scheduled.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}

/**
 * Complete briefing
 */
public function completeBriefing(CompleteBriefingRequest $request, Departure $departure)
{
    $this->authorize('update', $departure);

    try {
        $service = app(DepartureService::class);
        $service->completeBriefing(
            $departure,
            $request->boolean('acknowledgment_signed'),
            $request->input('notes'),
            $request->file('briefing_document'),
            $request->file('briefing_video'),
            $request->file('acknowledgment_file')
        );

        return back()->with('success', 'Pre-departure briefing completed.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}

/**
 * Mark ready to depart
 */
public function markReadyToDepart(Departure $departure)
{
    $this->authorize('update', $departure);

    try {
        $service = app(DepartureService::class);
        $service->markReadyToDepart($departure);

        return back()->with('success', 'Candidate marked as ready to depart.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}

/**
 * Record departure
 */
public function recordActualDeparture(Request $request, Departure $departure)
{
    $this->authorize('update', $departure);

    $validated = $request->validate([
        'actual_departure_time' => 'nullable|date',
    ]);

    try {
        $service = app(DepartureService::class);
        $service->recordDeparture($departure, $validated['actual_departure_time'] ?? null);

        return redirect()->route('departure.post-departure', $departure)
            ->with('success', 'Departure recorded. Proceed with post-departure tracking.');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

---

## Phase 8: Add Routes

```php
// routes/web.php
Route::middleware(['auth'])->prefix('departure')->name('departure.')->group(function () {
    Route::get('/enhanced-dashboard', [DepartureController::class, 'enhancedDashboard'])
        ->name('enhanced-dashboard');
    Route::get('/{departure}/checklist', [DepartureController::class, 'checklist'])
        ->name('checklist');
    Route::post('/{departure}/ptn', [DepartureController::class, 'updatePTN'])
        ->name('update-ptn');
    Route::post('/{departure}/protector', [DepartureController::class, 'updateProtector'])
        ->name('update-protector');
    Route::post('/{departure}/ticket', [DepartureController::class, 'updateTicket'])
        ->name('update-ticket');
    Route::post('/{departure}/briefing/schedule', [DepartureController::class, 'scheduleBriefing'])
        ->name('schedule-briefing');
    Route::post('/{departure}/briefing/complete', [DepartureController::class, 'completeBriefing'])
        ->name('complete-briefing');
    Route::post('/{departure}/ready', [DepartureController::class, 'markReadyToDepart'])
        ->name('mark-ready');
    Route::post('/{departure}/depart', [DepartureController::class, 'recordActualDeparture'])
        ->name('record-departure');
});
```

---

## Phase 9: Create Views

### 9.1 Enhanced Dashboard

Create `resources/views/departure/enhanced-dashboard.blade.php`:

**Layout:**
- Summary cards (Processing, Ready, Departed)
- PTN Status breakdown
- Protector Status breakdown
- Briefing Status breakdown
- Upcoming Flights table
- Ready to Depart list

### 9.2 Checklist View

Create `resources/views/departure/checklist.blade.php`:

**Sections:**
- Progress bar with percentage
- PTN card with form
- Protector card with form
- Ticket card with detailed form
- Briefing card with document/video uploads
- Final "Mark Ready to Depart" button
- "Record Departure" button (when ready)

---

## Phase 10: Testing

### 10.1 Unit Tests

```php
// tests/Unit/DepartureEnhancedTest.php
public function test_ptn_details_value_object()
public function test_ticket_details_value_object()
public function test_briefing_details_value_object()
public function test_departure_checklist_calculation()
public function test_can_mark_ready_requires_all_complete()
```

### 10.2 Feature Tests

```php
// tests/Feature/DepartureEnhancedTest.php
public function test_enhanced_dashboard_loads()
public function test_checklist_view_loads()
public function test_can_update_ptn()
public function test_can_update_protector_status()
public function test_can_update_ticket_details()
public function test_can_schedule_briefing()
public function test_can_complete_briefing_with_uploads()
public function test_cannot_mark_ready_without_all_complete()
public function test_can_record_departure()
```

---

## Validation Checklist

- [ ] Enhanced columns added to departures table
- [ ] ProtectorStatus enum created
- [ ] BriefingStatus enum created
- [ ] DepartureStatus enum created
- [ ] Value objects created (PTN, Ticket, Briefing)
- [ ] Departure model updated with new methods
- [ ] Service methods added
- [ ] Controller methods added
- [ ] Routes working
- [ ] Enhanced dashboard shows all status breakdowns
- [ ] Checklist view shows progress
- [ ] PTN update with evidence works
- [ ] Protector status update works
- [ ] Ticket details update works
- [ ] Briefing schedule/complete with video upload works
- [ ] Ready to depart requires all items complete
- [ ] Departure recording works
- [ ] All tests pass

---

## Files to Create

```
app/Enums/ProtectorStatus.php
app/Enums/BriefingStatus.php
app/Enums/DepartureStatus.php
app/ValueObjects/PTNDetails.php
app/ValueObjects/TicketDetails.php
app/ValueObjects/BriefingDetails.php
app/Http/Requests/UpdateTicketDetailsRequest.php
app/Http/Requests/CompleteBriefingRequest.php
database/migrations/YYYY_MM_DD_enhance_departures_table.php
resources/views/departure/enhanced-dashboard.blade.php
resources/views/departure/checklist.blade.php
tests/Unit/DepartureEnhancedTest.php
tests/Feature/DepartureEnhancedTest.php
docs/MODULE_6_DEPARTURE.md
```

## Files to Modify

```
app/Models/Departure.php
app/Services/DepartureService.php
app/Http/Controllers/DepartureController.php
routes/web.php
CLAUDE.md
README.md
```

---

## Success Criteria

Module 6 Enhancement is complete when:

1. PTN status with detailed tracking works
2. Protector status (Applied/Done/Pending/Deferred) works
3. Ticket details capture full flight information
4. Pre-departure briefing with document/video upload works
5. Departure checklist shows accurate progress
6. Ready to Depart requires all items complete
7. Departure recording updates candidate status
8. Enhanced dashboard shows all breakdowns
9. All tests pass
10. No regression in existing departure functionality

---

*End of Module 6 Implementation Prompt*
