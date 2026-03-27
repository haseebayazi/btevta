<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\SwitchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanySwitchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'from_employment_id',
        'to_employment_id',
        'switch_number',
        'switch_date',
        'reason',
        'status',
        'release_letter_path',
        'new_contract_path',
        'approval_document_path',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'switch_date' => 'date',
        'status' => SwitchStatus::class,
        'approved_at' => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function fromEmployment()
    {
        return $this->belongsTo(EmploymentHistory::class, 'from_employment_id');
    }

    public function toEmployment()
    {
        return $this->belongsTo(EmploymentHistory::class, 'to_employment_id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -----------------------------------------------------------------------
    // Methods
    // -----------------------------------------------------------------------

    public function approve(?string $notes = null): void
    {
        $this->status = SwitchStatus::APPROVED;
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->notes = $notes;
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Company switch approved');
    }

    public function complete(): void
    {
        if ($this->fromEmployment) {
            $this->fromEmployment->markAsPrevious();
        }

        if ($this->toEmployment) {
            $this->toEmployment->update(['status' => EmploymentStatus::CURRENT]);
        }

        $this->status = SwitchStatus::COMPLETED;
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->log('Company switch completed');
    }

    public function reject(string $reason): void
    {
        $this->status = SwitchStatus::REJECTED;
        $this->notes = $reason;
        $this->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Company switch rejected');
    }
}
