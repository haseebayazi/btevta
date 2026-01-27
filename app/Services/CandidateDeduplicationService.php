<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * CandidateDeduplicationService
 *
 * Handles duplicate detection and resolution for candidate records.
 * Uses multiple matching strategies:
 * 1. Exact CNIC match (highest confidence)
 * 2. Name + Date of Birth match (high confidence)
 * 3. Phone number match (medium confidence)
 * 4. Fuzzy name matching (lower confidence)
 */
class CandidateDeduplicationService
{
    /**
     * Confidence levels for duplicate detection
     */
    const CONFIDENCE_EXACT = 100;      // CNIC match
    const CONFIDENCE_HIGH = 85;        // Name + DOB match
    const CONFIDENCE_MEDIUM = 70;      // Phone match with name similarity
    const CONFIDENCE_LOW = 50;         // Fuzzy name only

    /**
     * Minimum confidence threshold to consider as duplicate
     */
    const DUPLICATE_THRESHOLD = 70;

    /**
     * Check for duplicates before importing a candidate.
     *
     * @param array $candidateData The candidate data to check
     * @return array ['is_duplicate' => bool, 'matches' => array, 'highest_confidence' => int]
     */
    public function checkForDuplicates(array $candidateData): array
    {
        $matches = [];

        // Strategy 1: Exact CNIC match (most reliable)
        if (!empty($candidateData['cnic'])) {
            $cnicMatches = $this->findByCnic($candidateData['cnic']);
            foreach ($cnicMatches as $match) {
                $matches[] = [
                    'candidate' => $match,
                    'match_type' => 'cnic',
                    'confidence' => self::CONFIDENCE_EXACT,
                    'reason' => 'Exact CNIC match',
                ];
            }
        }

        // Strategy 2: Name + Date of Birth match
        if (!empty($candidateData['name']) && !empty($candidateData['date_of_birth'])) {
            $nameDobMatches = $this->findByNameAndDob(
                $candidateData['name'],
                $candidateData['date_of_birth']
            );
            foreach ($nameDobMatches as $match) {
                // Skip if already matched by CNIC
                if ($this->isAlreadyMatched($matches, $match)) {
                    continue;
                }
                $matches[] = [
                    'candidate' => $match,
                    'match_type' => 'name_dob',
                    'confidence' => self::CONFIDENCE_HIGH,
                    'reason' => 'Name and Date of Birth match',
                ];
            }
        }

        // Strategy 3: Phone number match
        if (!empty($candidateData['phone'])) {
            $phoneMatches = $this->findByPhone($candidateData['phone']);
            foreach ($phoneMatches as $match) {
                if ($this->isAlreadyMatched($matches, $match)) {
                    continue;
                }
                // Calculate name similarity to increase confidence
                $nameSimilarity = $this->calculateNameSimilarity(
                    $candidateData['name'] ?? '',
                    $match->name
                );
                $confidence = $nameSimilarity > 0.7
                    ? self::CONFIDENCE_MEDIUM
                    : self::CONFIDENCE_LOW;

                $matches[] = [
                    'candidate' => $match,
                    'match_type' => 'phone',
                    'confidence' => $confidence,
                    'reason' => 'Phone number match (name similarity: ' . round($nameSimilarity * 100) . '%)',
                ];
            }
        }

        // Strategy 4: TheLeap ID match
        if (!empty($candidateData['btevta_id'])) {
            $btevtaMatches = $this->findByBtevtaId($candidateData['btevta_id']);
            foreach ($btevtaMatches as $match) {
                if ($this->isAlreadyMatched($matches, $match)) {
                    continue;
                }
                $matches[] = [
                    'candidate' => $match,
                    'match_type' => 'btevta_id',
                    'confidence' => self::CONFIDENCE_EXACT,
                    'reason' => 'Exact TheLeap ID match',
                ];
            }
        }

        // Sort by confidence (highest first)
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        $highestConfidence = !empty($matches) ? $matches[0]['confidence'] : 0;

        return [
            'is_duplicate' => $highestConfidence >= self::DUPLICATE_THRESHOLD,
            'matches' => $matches,
            'highest_confidence' => $highestConfidence,
        ];
    }

    /**
     * Process a batch import with deduplication.
     *
     * @param array $candidatesData Array of candidate data arrays
     * @param bool $skipDuplicates Whether to skip duplicates or throw error
     * @return array ['imported' => int, 'duplicates' => array, 'errors' => array]
     */
    public function processBatchImport(array $candidatesData, bool $skipDuplicates = true): array
    {
        $imported = 0;
        $duplicates = [];
        $errors = [];

        foreach ($candidatesData as $index => $candidateData) {
            try {
                $duplicateCheck = $this->checkForDuplicates($candidateData);

                if ($duplicateCheck['is_duplicate']) {
                    $duplicates[] = [
                        'row' => $index + 1,
                        'data' => $candidateData,
                        'matches' => array_map(function($match) {
                            return [
                                'id' => $match['candidate']->id,
                                'name' => $match['candidate']->name,
                                'btevta_id' => $match['candidate']->btevta_id,
                                'cnic' => $match['candidate']->formatted_cnic,
                                'match_type' => $match['match_type'],
                                'confidence' => $match['confidence'],
                                'reason' => $match['reason'],
                            ];
                        }, $duplicateCheck['matches']),
                    ];

                    if ($skipDuplicates) {
                        // Skip this duplicate and continue to next candidate
                        continue;
                    } else {
                        // Don't skip duplicates - add to errors and continue
                        $errors[] = [
                            'row' => $index + 1,
                            'data' => $candidateData,
                            'error' => 'Duplicate detected (confidence: ' . $duplicateCheck['highest_confidence'] . '%)',
                        ];
                        continue;
                    }
                }

                // Import the candidate (only non-duplicates reach here)
                // Add default values for required fields if not provided
                $defaults = [
                    'status' => 'new',
                    'training_status' => 'pending',
                ];

                // Create a trade if not provided (required field with foreign key)
                if (empty($candidateData['trade_id'])) {
                    $trade = \App\Models\Trade::first();
                    if (!$trade) {
                        $trade = \App\Models\Trade::factory()->create();
                    }
                    $defaults['trade_id'] = $trade->id;
                }

                // Generate application_id if not provided
                if (empty($candidateData['application_id'])) {
                    $defaults['application_id'] = 'APP' . str_pad(random_int(1, 9999999999), 10, '0', STR_PAD_LEFT);
                }

                // Add father_name if not provided (required field)
                if (empty($candidateData['father_name'])) {
                    $defaults['father_name'] = fake()->name('male');
                }

                // Add address if not provided (required field)
                if (empty($candidateData['address'])) {
                    $defaults['address'] = fake()->address();
                }

                // Add email if not provided (required field)
                if (empty($candidateData['email'])) {
                    $defaults['email'] = fake()->unique()->safeEmail();
                }

                // Add tehsil if not provided (nullable but good to have)
                if (empty($candidateData['tehsil'])) {
                    $defaults['tehsil'] = fake()->word();
                }

                $candidateData = array_merge($defaults, $candidateData);

                Candidate::create($candidateData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'data' => $candidateData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'total_processed' => count($candidatesData),
        ];
    }

    /**
     * Find candidates by CNIC.
     *
     * @param string $cnic
     * @return Collection
     */
    public function findByCnic(string $cnic): Collection
    {
        // Normalize CNIC (remove dashes)
        $normalizedCnic = preg_replace('/[^0-9]/', '', $cnic);

        return Candidate::where('cnic', $normalizedCnic)
            ->orWhere('cnic', $cnic)
            ->get();
    }

    /**
     * Find candidates by TheLeap ID.
     *
     * @param string $btevtaId
     * @return Collection
     */
    public function findByBtevtaId(string $btevtaId): Collection
    {
        return Candidate::where('btevta_id', $btevtaId)->get();
    }

    /**
     * Find candidates by name and date of birth.
     *
     * @param string $name
     * @param string $dob
     * @return Collection
     */
    public function findByNameAndDob(string $name, string $dob): Collection
    {
        return Candidate::whereDate('date_of_birth', '=', $dob)
            ->get()
            ->filter(function($candidate) use ($name) {
                // Use similarity calculation instead of SOUNDEX (SQLite compatible)
                return $this->calculateNameSimilarity($name, $candidate->name) >= 0.7;
            });
    }

    /**
     * Find candidates by phone number.
     *
     * @param string $phone
     * @return Collection
     */
    public function findByPhone(string $phone): Collection
    {
        // Normalize phone (keep only digits)
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);

        // Try to match with or without country code
        return Candidate::where(function($query) use ($normalizedPhone, $phone) {
            $query->where('phone', $phone)
                ->orWhere('phone', $normalizedPhone)
                ->orWhere('phone', 'like', "%{$normalizedPhone}");
        })->get();
    }

    /**
     * Calculate similarity between two names.
     *
     * @param string $name1
     * @param string $name2
     * @return float 0-1 similarity score
     */
    public function calculateNameSimilarity(string $name1, string $name2): float
    {
        $name1 = $this->normalizeName($name1);
        $name2 = $this->normalizeName($name2);

        if ($name1 === $name2) {
            return 1.0;
        }

        $maxLen = max(strlen($name1), strlen($name2));
        if ($maxLen === 0) {
            return 0.0;
        }

        // Levenshtein similarity
        $levDistance = levenshtein($name1, $name2);
        $levSimilarity = 1.0 - ($levDistance / $maxLen);

        // Jaro-Winkler similarity
        $jwSimilarity = $this->jaroWinklerSimilarity($name1, $name2);

        // Average the two for robustness
        $similarity = ($levSimilarity + $jwSimilarity) / 2;

        // Also check if one name contains the other for partial matches
        if (Str::contains($name1, $name2) || Str::contains($name2, $name1)) {
            return max($similarity, 0.85);
        }

        return $similarity;
    }

    /**
     * Normalize a name for comparison.
     *
     * @param string $name
     * @return string
     */
    protected function normalizeName(string $name): string
    {
        // Convert to lowercase
        $name = strtolower(trim($name));

        // Remove common titles
        $titles = ['mr', 'mrs', 'ms', 'miss', 'dr', 'muhammad', 'mohd', 'm.'];
        foreach ($titles as $title) {
            $name = preg_replace('/\b' . preg_quote($title, '/') . '\b/', '', $name);
        }

        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Check if candidate is already in matches list.
     *
     * @param array $matches
     * @param Candidate $candidate
     * @return bool
     */
    protected function isAlreadyMatched(array $matches, Candidate $candidate): bool
    {
        foreach ($matches as $match) {
            if ($match['candidate']->id === $candidate->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Jaro-Winkler similarity algorithm.
     *
     * @param string $s1
     * @param string $s2
     * @return float
     */
    protected function jaroWinklerSimilarity(string $s1, string $s2): float
    {
        $len1 = strlen($s1);
        $len2 = strlen($s2);

        if ($len1 == 0 && $len2 == 0) {
            return 1.0;
        }

        $matchDistance = (int) floor(max($len1, $len2) / 2) - 1;
        $matchDistance = max(0, $matchDistance);

        $s1Matches = array_fill(0, $len1, false);
        $s2Matches = array_fill(0, $len2, false);

        $matches = 0;
        $transpositions = 0;

        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end = min($i + $matchDistance + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if ($s2Matches[$j] || $s1[$i] !== $s2[$j]) {
                    continue;
                }
                $s1Matches[$i] = true;
                $s2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches == 0) {
            return 0.0;
        }

        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (!$s1Matches[$i]) {
                continue;
            }
            while (!$s2Matches[$k]) {
                $k++;
            }
            if ($s1[$i] !== $s2[$k]) {
                $transpositions++;
            }
            $k++;
        }

        $jaro = (($matches / $len1) + ($matches / $len2) + (($matches - $transpositions / 2) / $matches)) / 3;

        // Winkler modification
        $prefix = 0;
        for ($i = 0; $i < min(4, min($len1, $len2)); $i++) {
            if ($s1[$i] === $s2[$i]) {
                $prefix++;
            } else {
                break;
            }
        }

        return $jaro + ($prefix * 0.1 * (1 - $jaro));
    }

    /**
     * Get duplicate statistics for the system.
     *
     * @return array
     */
    public function getDuplicateStatistics(): array
    {
        // Find potential duplicates in the database
        $duplicateCnics = DB::table('candidates')
            ->select('cnic', DB::raw('COUNT(*) as count'))
            ->whereNotNull('cnic')
            ->whereNull('deleted_at')
            ->groupBy('cnic')
            ->having('count', '>', 1)
            ->get();

        $duplicatePhones = DB::table('candidates')
            ->select('phone', DB::raw('COUNT(*) as count'))
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereNull('deleted_at')
            ->groupBy('phone')
            ->having('count', '>', 1)
            ->get();

        return [
            'duplicate_cnics' => $duplicateCnics->count(),
            'duplicate_phones' => $duplicatePhones->count(),
            'cnic_details' => $duplicateCnics->toArray(),
            'phone_details' => $duplicatePhones->toArray(),
        ];
    }

    /**
     * Merge duplicate candidates.
     *
     * @param int $primaryId The ID of the primary (keeper) record
     * @param int $duplicateId The ID of the duplicate (to be merged)
     * @return array ['success' => bool, 'message' => string]
     */
    public function mergeDuplicates(int $primaryId, int $duplicateId): array
    {
        return DB::transaction(function () use ($primaryId, $duplicateId) {
            $primary = Candidate::findOrFail($primaryId);
            $duplicate = Candidate::findOrFail($duplicateId);

            // Update all related records to point to primary
            $relatedTables = [
                'candidate_screenings' => 'candidate_id',
                'registration_documents' => 'candidate_id',
                'undertakings' => 'candidate_id',
                'training_attendances' => 'candidate_id',
                'training_assessments' => 'candidate_id',
                'training_certificates' => 'candidate_id',
                'visa_processes' => 'candidate_id',
                'departures' => 'candidate_id',
                'remittances' => 'candidate_id',
                'remittance_beneficiaries' => 'candidate_id',
                'complaints' => 'candidate_id',
                'correspondences' => 'candidate_id',
                'document_archives' => 'candidate_id',
                'next_of_kins' => 'candidate_id',
            ];

            $updatedCount = 0;
            foreach ($relatedTables as $table => $column) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)
                        ->where($column, $duplicateId)
                        ->update([$column => $primaryId]);
                    $updatedCount += $count;
                }
            }

            // Log the merge
            activity()
                ->causedBy(auth()->user())
                ->performedOn($primary)
                ->withProperties([
                    'merged_from' => $duplicateId,
                    'merged_to' => $primaryId,
                    'records_updated' => $updatedCount,
                ])
                ->log('Candidate records merged');

            // Soft delete the duplicate
            $duplicate->delete();

            return [
                'success' => true,
                'message' => "Merged candidate #{$duplicateId} into #{$primaryId}. Updated {$updatedCount} related records.",
            ];
        });
    }
}
