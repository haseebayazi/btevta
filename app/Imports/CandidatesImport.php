<?php

namespace App\Imports;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CandidatesImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, SkipsOnError
{
    use SkipsErrors;

    protected $batchId;
    protected $campusId;
    protected $importResults = [
        'success' => 0,
        'failed' => 0,
        'duplicates' => 0,
        'errors' => []
    ];
    protected $campusesCache = [];
    protected $tradesCache = [];

    public function __construct($batchId = null, $campusId = null)
    {
        $this->batchId = $batchId;
        $this->campusId = $campusId;

        // PERFORMANCE: Pre-load campuses and trades to avoid N+1 queries
        $campuses = Campus::all();
        foreach ($campuses as $campus) {
            $this->campusesCache[strtolower($campus->name)] = $campus->id;
        }

        $trades = Trade::all();
        foreach ($trades as $trade) {
            $this->tradesCache[strtolower($trade->name)] = $trade->id;
            if ($trade->code) {
                $this->tradesCache[strtolower($trade->code)] = $trade->id;
            }
        }
    }

    /**
     * Transform Excel row to Candidate model
     */
    public function model(array $row)
    {
        // Check for duplicates
        $existing = Candidate::where('cnic', $this->cleanCNIC($row['cnic'] ?? ''))
            ->orWhere('application_id', $row['application_id'] ?? null)
            ->first();

        if ($existing) {
            $this->importResults['duplicates']++;
            Log::warning('Duplicate candidate found', ['cnic' => $row['cnic'], 'application_id' => $row['application_id']]);
            return null;
        }

        // PERFORMANCE: Find campus from pre-loaded cache instead of database query
        $campusId = $this->campusId;
        if (!empty($row['campus'])) {
            $campusKey = strtolower(trim($row['campus']));
            // Try exact match first
            if (isset($this->campusesCache[$campusKey])) {
                $campusId = $this->campusesCache[$campusKey];
            } else {
                // Try partial match
                foreach ($this->campusesCache as $name => $id) {
                    if (str_contains($name, $campusKey) || str_contains($campusKey, $name)) {
                        $campusId = $id;
                        break;
                    }
                }
            }
        }

        // PERFORMANCE: Find trade from pre-loaded cache instead of database query
        $tradeId = null;
        if (!empty($row['trade'])) {
            $tradeKey = strtolower(trim($row['trade']));
            // Try exact match first
            if (isset($this->tradesCache[$tradeKey])) {
                $tradeId = $this->tradesCache[$tradeKey];
            } else {
                // Try partial match
                foreach ($this->tradesCache as $name => $id) {
                    if (str_contains($name, $tradeKey) || str_contains($tradeKey, $name)) {
                        $tradeId = $id;
                        break;
                    }
                }
            }
        }

        // Parse date
        $dateOfBirth = null;
        if (!empty($row['date_of_birth'])) {
            try {
                $dateOfBirth = Carbon::parse($row['date_of_birth']);
            } catch (\Exception $e) {
                Log::warning('Invalid date format', ['date' => $row['date_of_birth']]);
            }
        }

        try {
            $candidate = new Candidate([
                'name' => $row['name'] ?? $row['candidate_name'],
                'father_name' => $row['father_name'] ?? $row['fathers_name'],
                'cnic' => $this->cleanCNIC($row['cnic']),
                'phone' => $this->cleanPhone($row['phone'] ?? $row['mobile']),
                'email' => $row['email'] ?? null,
                'date_of_birth' => $dateOfBirth,
                'gender' => strtolower($row['gender'] ?? 'male'),
                'address' => $row['address'] ?? '',
                'district' => $row['district'] ?? '',
                'province' => $row['province'] ?? 'Punjab',
                'qualification' => $row['qualification'] ?? $row['education'] ?? '',
                'experience_years' => is_numeric($row['experience'] ?? 0) ? $row['experience'] : 0,
                'blood_group' => $row['blood_group'] ?? null,
                'marital_status' => $row['marital_status'] ?? 'single',
                'passport_number' => $row['passport_no'] ?? $row['passport'] ?? null,
                'campus_id' => $campusId,
                'batch_id' => $this->batchId,
                'trade_id' => $tradeId,
                'status' => 'new',
                'application_id' => $row['application_id'] ?? Candidate::generateApplicationId(),
            ]);

            $this->importResults['success']++;
            return $candidate;

        } catch (\Exception $e) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Row error: " . $e->getMessage();
            Log::error('Import row error', ['error' => $e->getMessage(), 'row' => $row]);
            return null;
        }
    }

    /**
     * Validation rules for import
     */
    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.cnic' => 'required|string',
            '*.phone' => 'required|string',
            '*.district' => 'required|string',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Candidate name is required',
            'cnic.required' => 'CNIC is required',
            'phone.required' => 'Phone number is required',
            'district.required' => 'District is required',
        ];
    }

    /**
     * Batch size for inserts
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Clean CNIC format
     */
    protected function cleanCNIC($cnic)
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $cnic);
        
        // Ensure it's 13 digits
        if (strlen($cleaned) !== 13) {
            throw new \Exception("Invalid CNIC format: {$cnic}");
        }
        
        return $cleaned;
    }

    /**
     * Clean phone number
     */
    protected function cleanPhone($phone)
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Add Pakistan country code if not present
        if (strlen($cleaned) === 10) {
            $cleaned = '92' . $cleaned;
        } elseif (strlen($cleaned) === 11 && substr($cleaned, 0, 1) === '0') {
            $cleaned = '92' . substr($cleaned, 1);
        }
        
        return $cleaned;
    }

    /**
     * Get import results
     */
    public function getImportResults()
    {
        return $this->importResults;
    }
}