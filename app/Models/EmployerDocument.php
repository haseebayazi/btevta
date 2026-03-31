<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployerDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'document_type',
        'document_name',
        'document_path',
        'document_number',
        'issue_date',
        'expiry_date',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    const TYPE_LICENSE = 'license';
    const TYPE_REGISTRATION = 'registration';
    const TYPE_PERMISSION = 'permission';
    const TYPE_CONTRACT_TEMPLATE = 'contract_template';
    const TYPE_OTHER = 'other';

    public static function documentTypes(): array
    {
        return [
            self::TYPE_LICENSE => 'Business License',
            self::TYPE_REGISTRATION => 'Company Registration',
            self::TYPE_PERMISSION => 'Work Permission',
            self::TYPE_CONTRACT_TEMPLATE => 'Contract Template',
            self::TYPE_OTHER => 'Other Document',
        ];
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpiring(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }
}
