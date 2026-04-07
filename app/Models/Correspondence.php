<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Correspondence extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'correspondences';

    // ─── Direction constants ──────────────────────────────────────────────────
    const TYPE_INCOMING = 'incoming';
    const TYPE_OUTGOING = 'outgoing';

    // ─── Medium/format constants (for UI display) ─────────────────────────────
    const MEDIUM_EMAIL  = 'email';
    const MEDIUM_LETTER = 'letter';
    const MEDIUM_MEMO   = 'memo';
    const MEDIUM_NOTICE = 'notice';
    const MEDIUM_OTHER  = 'other';

    // ─── Priority constants ───────────────────────────────────────────────────
    const PRIORITY_LOW    = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    // ─── Status constants ─────────────────────────────────────────────────────
    const STATUS_PENDING     = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_REPLIED     = 'replied';
    const STATUS_CLOSED      = 'closed';

    // ─── Organization type constants ──────────────────────────────────────────
    const ORG_BTEVTA     = 'btevta';
    const ORG_OEP        = 'oep';
    const ORG_EMBASSY    = 'embassy';
    const ORG_CAMPUS     = 'campus';
    const ORG_GOVERNMENT = 'government';
    const ORG_PRIVATE    = 'private';
    const ORG_NGO        = 'ngo';
    const ORG_INTERNAL   = 'internal';
    const ORG_OTHER      = 'other';

    protected $fillable = [
        // Relations
        'campus_id',
        'oep_id',
        'candidate_id',
        'assigned_to',
        'created_by',
        'updated_by',

        // Core fields (from original schema)
        'subject',
        'message',
        'attachment_path',
        'requires_reply',
        'replied',
        'sent_at',
        'replied_at',
        'status',

        // Added columns
        'type',
        'file_reference_number',
        'organization_type',
        'sender',
        'recipient',
        'priority_level',
        'description',
        'notes',
        'due_date',
    ];

    protected $casts = [
        'requires_reply' => 'boolean',
        'replied'        => 'boolean',
        'sent_at'        => 'datetime',
        'replied_at'     => 'datetime',
        'due_date'       => 'date',
    ];

    protected $attributes = [
        'status'         => self::STATUS_PENDING,
        'type'           => self::TYPE_INCOMING,
        'priority_level' => self::PRIORITY_NORMAL,
        'requires_reply' => false,
        'replied'        => false,
    ];

    // ─── Static helpers ───────────────────────────────────────────────────────

    public static function getDirectionTypes(): array
    {
        return [
            self::TYPE_INCOMING => 'Incoming',
            self::TYPE_OUTGOING => 'Outgoing',
        ];
    }

    public static function getMediumTypes(): array
    {
        return [
            self::MEDIUM_EMAIL  => 'Email',
            self::MEDIUM_LETTER => 'Letter',
            self::MEDIUM_MEMO   => 'Memo',
            self::MEDIUM_NOTICE => 'Notice',
            self::MEDIUM_OTHER  => 'Other',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW    => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH   => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING     => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_REPLIED     => 'Replied',
            self::STATUS_CLOSED      => 'Closed',
        ];
    }

    public static function getOrganizationTypes(): array
    {
        return [
            self::ORG_BTEVTA     => 'BTEVTA',
            self::ORG_OEP        => 'OEP',
            self::ORG_EMBASSY    => 'Embassy',
            self::ORG_CAMPUS     => 'Campus',
            self::ORG_GOVERNMENT => 'Government',
            self::ORG_PRIVATE    => 'Private',
            self::ORG_NGO        => 'NGO',
            self::ORG_INTERNAL   => 'Internal',
            self::ORG_OTHER      => 'Other',
        ];
    }

    /**
     * Auto-generate a file reference number in the format COR-YYYYMM-NNNNN.
     */
    public static function generateFileReferenceNumber(): string
    {
        $year  = date('Y');
        $month = date('m');
        $prefix = "COR-{$year}{$month}-";

        $last = self::where('file_reference_number', 'like', "{$prefix}%")
            ->orderByDesc('file_reference_number')
            ->value('file_reference_number');

        $next = $last ? (intval(substr($last, -5)) + 1) : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function candidate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function campus(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Oep::class);
    }

    public function assignee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Alias for creator() used by controllers and views. */
    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->creator();
    }

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Query scopes ─────────────────────────────────────────────────────────

    /** Correspondence waiting for a reply. */
    public function scopePendingReply($query)
    {
        return $query->where('requires_reply', true)->where('replied', false);
    }

    /** Urgent priority correspondence. */
    public function scopeUrgent($query)
    {
        return $query->where('priority_level', self::PRIORITY_URGENT);
    }

    /** Correspondence that has passed its due date without a reply. */
    public function scopeOverdue($query)
    {
        return $query->where('requires_reply', true)
            ->where('replied', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /** Filter by direction type. */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ─── Boot hooks ───────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $correspondence) {
            if (empty($correspondence->file_reference_number)) {
                $correspondence->file_reference_number = self::generateFileReferenceNumber();
            }

            if (empty($correspondence->sent_at)) {
                $correspondence->sent_at = now();
            }

            if (auth()->check() && empty($correspondence->created_by)) {
                $correspondence->created_by = auth()->id();
            }
        });

        static::updating(function (self $correspondence) {
            if (auth()->check()) {
                $correspondence->updated_by = auth()->id();
            }
        });
    }
}
