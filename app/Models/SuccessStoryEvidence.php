<?php

namespace App\Models;

use App\Enums\StoryEvidenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuccessStoryEvidence extends Model
{
    use HasFactory;

    protected $table = 'success_story_evidence';

    protected $fillable = [
        'success_story_id',
        'evidence_type',
        'title',
        'description',
        'file_path',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'is_primary',
        'display_order',
        'uploaded_by',
    ];

    protected $casts = [
        'evidence_type' => StoryEvidenceType::class,
        'is_primary'    => 'boolean',
        'file_size'     => 'integer',
        'display_order' => 'integer',
    ];

    protected $hidden = ['file_path', 'thumbnail_path'];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function successStory()
    {
        return $this->belongsTo(SuccessStory::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'Unknown';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->file_size;
        $unit  = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    public function getIsVideoAttribute(): bool
    {
        return in_array($this->file_extension, ['mp4', 'mov', 'avi', 'webm']);
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($evidence) {
            if (auth()->check()) {
                $evidence->uploaded_by = $evidence->uploaded_by ?? auth()->id();
            }
        });
    }
}
