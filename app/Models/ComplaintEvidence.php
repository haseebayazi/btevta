<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplaintEvidence extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'complaint_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
        'description',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getIsImageAttribute()
    {
        return in_array(strtolower($this->file_extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    public function getIsPdfAttribute()
    {
        return strtolower($this->file_extension) === 'pdf';
    }

    public function getIsDocumentAttribute()
    {
        return in_array(strtolower($this->file_extension), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evidence) {
            if (auth()->check()) {
                $evidence->uploaded_by = $evidence->uploaded_by ?? auth()->id();
                $evidence->created_by = auth()->id();
            }
        });

        static::updating(function ($evidence) {
            if (auth()->check()) {
                $evidence->updated_by = auth()->id();
            }
        });
    }
}
