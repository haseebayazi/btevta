<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingAttendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'batch_id', 'date', 'status', 'remarks'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}