<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class RemittanceReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'remittance_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'document_type',
        'is_verified',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    // Relationships
    public function remittance()
    {
        return $this->belongsTo(Remittance::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessors
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute()
    {
        if ($this->file_size < 1024) {
            return $this->file_size . ' B';
        } elseif ($this->file_size < 1048576) {
            return round($this->file_size / 1024, 2) . ' KB';
        } else {
            return round($this->file_size / 1048576, 2) . ' MB';
        }
    }

    public function getDocumentTypeLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->document_type));
    }

    // Methods
    public function verify($userId, $notes = null)
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);

        // Update parent remittance
        $this->remittance->update(['has_proof' => true]);
    }

    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($receipt) {
            $receipt->deleteFile();
        });
    }
}
