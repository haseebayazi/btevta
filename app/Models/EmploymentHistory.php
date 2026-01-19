<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'departure_id',
        'switch_number',
        'company_name',
        'work_location',
        'salary',
        'salary_currency',
        'job_terms',
        'commencement_date',
        'special_conditions',
        'switch_date',
        'reason_for_switch',
        'recorded_by',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'commencement_date' => 'date',
        'switch_date' => 'date',
        'switch_number' => 'integer',
    ];

    /**
     * Get the departure record
     */
    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }

    /**
     * Get the user who recorded this history
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
