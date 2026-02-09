<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Training;
use App\Models\Batch;
use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use App\Enums\CandidateStatus;

class Module3To4HandoffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that starting training from registration creates a Training record.
     */
    public function test_registration_start_training_creates_training_record(): void
    {
        // Setup
        $admin = User::factory()->create(['role' => 'admin']);
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);
        
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::REGISTERED->value,
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);
        
        // Create required documents
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'status' => 'verified',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'education',
            'status' => 'verified',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'photo',
            'status' => 'verified',
        ]);
        
        // Create next of kin and link to candidate
        $nextOfKin = NextOfKin::factory()->create(['candidate_id' => $candidate->id]);
        $candidate->update(['next_of_kin_id' => $nextOfKin->id]);
        
        // Create undertaking
        Undertaking::factory()->create([
            'candidate_id' => $candidate->id,
            'is_completed' => true,
        ]);
        
        // Verify no Training record exists yet
        $this->assertDatabaseMissing('trainings', ['candidate_id' => $candidate->id]);
        
        // Act: Start training
        $response = $this->actingAs($admin)
            ->post(route('registration.start-training', $candidate), [
                'batch_id' => $batch->id,
            ]);
        
        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify candidate status updated
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::TRAINING->value, $candidate->status);
        $this->assertEquals('in_progress', $candidate->training_status);
        $this->assertEquals($batch->id, $candidate->batch_id);
        $this->assertNotNull($candidate->training_start_date);
        
        // Verify Training record created (CRITICAL)
        $this->assertDatabaseHas('trainings', [
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'status' => 'not_started',
            'technical_training_status' => 'not_started',
            'soft_skills_status' => 'not_started',
        ]);
        
        $training = Training::where('candidate_id', $candidate->id)->first();
        $this->assertNotNull($training);
        $this->assertEquals($batch->id, $training->batch_id);
    }
}
