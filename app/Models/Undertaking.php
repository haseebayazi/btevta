<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Undertaking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'undertaking_type',
        'content',
        'signature_path',
        'signed_at',
        'is_completed',
        'witness_name',
        'witness_cnic',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set default content if not provided
            if (empty($model->content)) {
                $model->content = 'Undertaking for candidate registration and training program.';
            }

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