<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Correspondence extends Model
{
    use HasFactory, SoftDeletes;

    // AUDIT FIX: Table name corrected to match migration (plural form)
    protected $table = 'correspondences';

    protected $fillable = [
        // Actual columns from 2025_10_31_165531_create_correspondences_table migration
        'campus_id',
        'oep_id',
        'candidate_id',
        'subject',
        'message',
        'requires_reply',
        'replied',
        'sent_at',
        'replied_at',
        'status',
        'attachment_path',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'replied_at' => 'datetime',
        'requires_reply' => 'boolean',
        'replied' => 'boolean',
    ];

    protected $attributes = [
        // Disabled - these columns don't exist in current schema
        // 'status' => 'pending',
        // 'priority_level' => 'normal',
        // 'correspondence_type' => 'letter',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * SECURITY: Hide document paths to prevent unauthorized access
     */
    protected $hidden = [
        'document_path',
    ];

    // Type constants
    const TYPE_EMAIL = 'email';
    const TYPE_LETTER = 'letter';
    const TYPE_MEMO = 'memo';
    const TYPE_NOTICE = 'notice';
    const TYPE_OTHER = 'other';

    // Priority constants
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_REPLIED = 'replied';
    const STATUS_CLOSED = 'closed';

    public static function getTypes()
    {
        return [
            self::TYPE_EMAIL => 'Email',
            self::TYPE_LETTER => 'Letter',
            self::TYPE_MEMO => 'Memo',
            self::TYPE_NOTICE => 'Notice',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public static function getPriorities()
    {
        return [
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_LOW => 'Low',
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_REPLIED => 'Replied',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for creator() relationship.
     */
    public function createdBy()
    {
        return $this->creator();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    // Scopes
    public function scopePendingReply($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority_level', self::PRIORITY_URGENT);
    }

    // Helper Methods
    public static function generateFileReferenceNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        $lastCorrespondence = self::where('file_reference_number', 'like', "COR-{$year}{$month}%")
                                   ->orderBy('file_reference_number', 'desc')
                                   ->first();
        
        if ($lastCorrespondence && $lastCorrespondence->file_reference_number) {
            $lastNumber = intval(substr($lastCorrespondence->file_reference_number, -5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return 'COR-' . $year . $month . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($correspondence) {
            // Disabled - file_reference_number column doesn't exist in current schema
            // if (empty($correspondence->file_reference_number)) {
            //     $correspondence->file_reference_number = self::generateFileReferenceNumber();
            // }
            
            if (empty($correspondence->correspondence_date)) {
                $correspondence->correspondence_date = now();
            }
            
            if (auth()->check()) {
                $correspondence->created_by = auth()->id();
            }
        });

        static::updating(function ($correspondence) {
            if (auth()->check()) {
                $correspondence->updated_by = auth()->id();
            }
        });
    }
}