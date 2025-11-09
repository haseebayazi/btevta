<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Undertaking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_id', 'undertaking_date', 'signed_by', 'terms', 'remarks'
    ];

    protected $casts = [
        'undertaking_date' => 'date',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}