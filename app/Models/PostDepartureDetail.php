<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostDepartureDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'departure_id',
        // Residency & Identity
        'residency_proof_path',
        'residency_number',
        'residency_expiry',
        'foreign_license_path',
        'foreign_license_number',
        'foreign_mobile_number',
        'foreign_bank_name',
        'foreign_bank_account',
        'tracking_app_registration',
        'final_contract_path',
        // Final Employment Details
        'company_name',
        'employer_name',
        'employer_designation',
        'employer_contact',
        'work_location',
        'final_salary',
        'salary_currency',
        'final_job_terms',
        'job_commencement_date',
        'special_conditions',
    ];

    protected $casts = [
        'residency_expiry' => 'date',
        'final_salary' => 'decimal:2',
        'job_commencement_date' => 'date',
    ];

    /**
     * Get the departure record
     */
    public function departure()
    {
        return $this->belongsTo(Departure::class);
    }
}
