<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentRenewalRequest extends Model
{
    protected $fillable = [
        'candidate_id',
        'document_type',
        'documentable_type',
        'documentable_id',
        'current_expiry_date',
        'requested_date',
        'status',
        'new_document_path',
        'new_expiry_date',
        'notes',
        'requested_by',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'current_expiry_date' => 'date',
        'requested_date' => 'date',
        'new_expiry_date' => 'date',
        'processed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
