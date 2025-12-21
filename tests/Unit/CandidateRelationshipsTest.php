<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\TrainingCertificate;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test certificate() relationship returns hasOne
     */
    public function test_certificate_relationship_returns_latest(): void
    {
        $candidate = Candidate::factory()->create();

        // Relationship should be defined
        $this->assertNotNull($candidate->certificate());

        // Should return hasOne type relationship
        $relation = $candidate->certificate();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
    }

    /**
     * Test attendances() is alias for trainingAttendances()
     */
    public function test_attendances_alias_relationship(): void
    {
        $candidate = Candidate::factory()->create();

        // Both methods should return equivalent relationships
        $attendances = $candidate->attendances();
        $trainingAttendances = $candidate->trainingAttendances();

        $this->assertEquals(
            get_class($attendances),
            get_class($trainingAttendances)
        );
    }

    /**
     * Test assessments() is alias for trainingAssessments()
     */
    public function test_assessments_alias_relationship(): void
    {
        $candidate = Candidate::factory()->create();

        // Both methods should return equivalent relationships
        $assessments = $candidate->assessments();
        $trainingAssessments = $candidate->trainingAssessments();

        $this->assertEquals(
            get_class($assessments),
            get_class($trainingAssessments)
        );
    }

    /**
     * Test trainingCertificates() relationship returns hasMany
     */
    public function test_training_certificates_relationship(): void
    {
        $candidate = Candidate::factory()->create();

        $relation = $candidate->trainingCertificates();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    /**
     * Test certificate returns latest certificate when multiple exist
     */
    public function test_certificate_returns_latest_when_multiple_exist(): void
    {
        $candidate = Candidate::factory()->create();

        // Create multiple certificates with different timestamps
        TrainingCertificate::factory()->create([
            'candidate_id' => $candidate->id,
            'certificate_number' => 'CERT-001',
            'created_at' => now()->subDays(10),
        ]);

        $latestCert = TrainingCertificate::factory()->create([
            'candidate_id' => $candidate->id,
            'certificate_number' => 'CERT-002',
            'created_at' => now(),
        ]);

        // Reload candidate and get certificate
        $candidate->refresh();
        $certificate = $candidate->certificate;

        $this->assertNotNull($certificate);
        $this->assertEquals('CERT-002', $certificate->certificate_number);
    }
}
