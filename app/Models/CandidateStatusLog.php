<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'from_status',
        'to_status',
        'reason',
        'notes',
        'context',
        'changed_by',
        'changed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'context' => 'array',
        'changed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
