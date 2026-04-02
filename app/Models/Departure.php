<?php

namespace App\Models;

use App\Enums\BriefingStatus;
use App\Enums\DepartureStatus;
use App\Enums\ProtectorStatus;
use App\ValueObjects\BriefingDetails;
use App\ValueObjects\PTNDetails;
use App\ValueObjects\TicketDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'departure_date',
        'flight_number',
        'destination',
        'pre_departure_briefing',
        'briefing_date',
        'briefing_completed',
        'ready_for_departure',
        'iqama_number',
        'iqama_issue_date',
        'iqama_expiry_date',
        'post_arrival_medical_path',
        'absher_registered',
        'absher_registration_date',
        'absher_id',
        'absher_verification_status',
        'qiwa_id',
        'qiwa_activated',
        'qiwa_activation_date',
        'qiwa_status',
        'salary_amount',
        'salary_currency',
        'first_salary_date',
        'salary_confirmed',
        'salary_confirmed_by',
        'salary_confirmed_at',
        'salary_confirmation_date',
        'salary_remarks',
        'ninety_day_report_submitted',
        'ninety_day_compliance_checked',
        'ninety_day_compliance_status',
        'ninety_day_compliance_issues',
        'ninety_day_compliance_checked_at',
        'remarks',
        'created_by',
        'updated_by',
        // Additional fields for service compatibility
        'pre_briefing_date',
        'pre_briefing_conducted_by',
        'briefing_topics',
        'briefing_remarks',
        'current_stage',
        'airport',
        'country_code',
        'departure_remarks',
        'medical_report_path',
        'medical_report_date',
        'accommodation_type',
        'accommodation_address',
        'accommodation_status',
        'accommodation_verified_date',
        'accommodation_remarks',
        'employer_name',
        'employer_contact',
        'employer_address',
        'employer_id_number',
        'communication_logs',
        'last_contact_date',
        'compliance_verified_date',
        'compliance_remarks',
        'issues',
        'return_date',
        'return_reason',
        'return_remarks',
        'salary_proof_path',
        // WASL v3 Enhancement Fields (Phase 1)
        'ptn_status',
        'ptn_issued_at',
        'ptn_deferred_reason',
        'protector_status',
        'protector_applied_at',
        'protector_done_at',
        'protector_deferred_reason',
        'ticket_date',
        'ticket_time',
        'departure_platform',
        'landing_platform',
        'flight_type',
        'pre_departure_doc_path',
        'pre_departure_video_path',
        'final_departure_status',
        // Module 6 Enhancement Fields (Phase 2)
        'ptn_details',
        'protector_details',
        'ticket_details',
        'briefing_status',
        'briefing_details',
        'departure_status',
        'departed_at',
        'ptn_number',
        'ticket_path',
        'visa_process_id',
        'oep_id',
        'status',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'briefing_date' => 'date',
        'pre_briefing_date' => 'date',
        'iqama_issue_date' => 'date',
        'iqama_expiry_date' => 'date',
        'absher_registration_date' => 'date',
        'qiwa_activation_date' => 'date',
        'first_salary_date' => 'date',
        'salary_confirmation_date' => 'date',
        'salary_confirmed_at' => 'datetime',
        'ninety_day_compliance_checked_at' => 'datetime',
        'accommodation_verified_date' => 'date',
        'last_contact_date' => 'date',
        'medical_report_date' => 'date',
        'compliance_verified_date' => 'date',
        'return_date' => 'date',
        'salary_amount' => 'float',
        'pre_departure_briefing' => 'boolean',
        'briefing_completed' => 'boolean',
        'ready_for_departure' => 'boolean',
        'absher_registered' => 'boolean',
        'qiwa_activated' => 'boolean',
        'salary_confirmed' => 'boolean',
        'ninety_day_report_submitted' => 'boolean',
        'ninety_day_compliance_checked' => 'boolean',
        // WASL v3 Enhancement Field Casts
        'ptn_issued_at' => 'datetime',
        'protector_applied_at' => 'datetime',
        'protector_done_at' => 'datetime',
        'ticket_date' => 'date',
        // Module 6 Enhancement Casts
        'ptn_details' => 'array',
        'protector_details' => 'array',
        'ticket_details' => 'array',
        'briefing_details' => 'array',
        'protector_status' => ProtectorStatus::class,
        'briefing_status' => BriefingStatus::class,
        'departure_status' => DepartureStatus::class,
        'departed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide sensitive employment and financial information
     */
    protected $hidden = [
        'iqama_number',
        'qiwa_id',
        'salary_amount',
        'post_arrival_medical_path',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the OEP associated with this departure's candidate.
     *
     * HasOneThrough is not suitable here because the intermediate table (candidates)
     * does not have a FK pointing to departures; instead departures.candidate_id points
     * to candidates.id. Use $departure->candidate->oep for direct access.
     */
    public function getOepAttribute()
    {
        return $this->candidate?->oep;
    }

    /**
     * Get the post-departure detail record for this departure.
     */
    public function postDepartureDetail()
    {
        return $this->hasOne(PostDepartureDetail::class);
    }

    /**
     * Get all remittances for this departure.
     * AUDIT FIX 2026-01-09: Added missing relationship documented in SYSTEM_MAP.md
     */
    public function remittances()
    {
        return $this->hasMany(Remittance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function salaryConfirmer()
    {
        return $this->belongsTo(User::class, 'salary_confirmed_by');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeSearch($query, $term)
    {
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function ($q) use ($escapedTerm) {
            $q->where('flight_number', 'like', "%{$escapedTerm}%")
                ->orWhere('destination', 'like', "%{$escapedTerm}%")
                ->orWhereHas('candidate', function ($subQ) use ($escapedTerm) {
                    $subQ->where('name', 'like', "%{$escapedTerm}%")
                        ->orWhere('cnic', 'like', "%{$escapedTerm}%")
                        ->orWhere('btevta_id', 'like', "%{$escapedTerm}%");
                });
        });
    }

    // -----------------------------------------------------------------------
    // Module 6 Accessors (Value Object wrappers)
    // -----------------------------------------------------------------------

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

    // -----------------------------------------------------------------------
    // Module 6 Business Logic
    // -----------------------------------------------------------------------

    /**
     * Check if all departure requirements are complete so the record can be
     * marked "Ready to Depart".
     */
    public function canMarkReadyToDepart(): bool
    {
        return $this->ptn_details_object->isIssued()
            && $this->protector_status === ProtectorStatus::DONE
            && $this->ticket_details_object->isComplete()
            && $this->briefing_details_object->isComplete();
    }

    /**
     * Return a structured checklist of all departure requirements.
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
                'complete' => $this->protector_status?->value === 'done',
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
     * Update PTN details and persist to the JSON column.
     */
    public function updatePTN(string $ptnNumber, string $issuedDate, ?string $expiryDate = null, $evidenceFile = null): void
    {
        $evidencePath = $this->ptn_details_object->evidencePath;

        if ($evidenceFile) {
            $evidencePath = $this->uploadFile($evidenceFile, 'ptn');
        }

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
     * Update protector status and persist details.
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
     * Update ticket details with full flight information.
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

        $this->save();

        $this->logActivity('Ticket details updated', [
            'airline' => $ticketData['airline'] ?? null,
            'flight' => $ticketData['flight_number'] ?? null,
        ]);
    }

    /**
     * Schedule a pre-departure briefing.
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
     * Mark the pre-departure briefing as completed with optional media uploads.
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
     * Mark the candidate as ready to depart (all requirements must be met).
     */
    public function markReadyToDepart(): void
    {
        if (! $this->canMarkReadyToDepart()) {
            throw new \Exception('All departure requirements must be complete before marking ready to depart.');
        }

        $this->departure_status = DepartureStatus::READY_TO_DEPART;
        $this->save();

        $this->candidate->update(['status' => 'ready_to_depart']);

        $this->logActivity('Marked ready to depart');
    }

    /**
     * Record the actual departure.
     */
    public function recordDeparture(?string $actualDepartureTime = null): void
    {
        $this->departure_status = DepartureStatus::DEPARTED;
        $this->departed_at = $actualDepartureTime ? \Carbon\Carbon::parse($actualDepartureTime) : now();
        $this->save();

        $this->candidate->update(['status' => 'departed']);

        $this->logActivity('Departed', ['departed_at' => $this->departed_at]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Store a file under the candidate's departure folder (private disk).
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
     * Log an activity via Spatie Activity Log.
     */
    protected function logActivity(string $message, array $properties = []): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->log($message);
    }

    // -----------------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------------

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
