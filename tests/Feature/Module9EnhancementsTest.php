<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Enums\ComplaintEvidenceCategory;
use App\Enums\StoryStatus;
use App\Enums\StoryType;
use App\Enums\StoryEvidenceType;
use App\Models\Candidate;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\ComplaintTemplate;
use App\Models\SuccessStory;
use App\Models\SuccessStoryEvidence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Module 9 Enhancement Tests
 * Tests for: StoryType enum, StoryStatus workflow, StoryEvidenceType,
 *            ComplaintEvidenceCategory, ComplaintTemplate,
 *            Enhanced dashboard, Templates, Categorized evidence, Evidence verification
 */
class Module9EnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $candidate;
    protected Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->admin     = User::factory()->create(['role' => 'super_admin']);
        $this->candidate = Candidate::factory()->create();
        $this->complaint = Complaint::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);
    }

    // =========================================================================
    // ENUMS
    // =========================================================================

    #[Test]
    public function story_type_enum_has_correct_labels()
    {
        $this->assertEquals('Employment Success', StoryType::EMPLOYMENT->label());
        $this->assertEquals('Career Growth', StoryType::CAREER_GROWTH->label());
        $this->assertEquals('Remittance Impact', StoryType::REMITTANCE->label());
    }

    #[Test]
    public function story_status_enum_has_correct_workflow_methods()
    {
        $this->assertTrue(StoryStatus::DRAFT->canSubmitForReview());
        $this->assertFalse(StoryStatus::PUBLISHED->canSubmitForReview());
        $this->assertTrue(StoryStatus::PENDING_REVIEW->canApprove());
        $this->assertFalse(StoryStatus::DRAFT->canApprove());
        $this->assertTrue(StoryStatus::APPROVED->canPublish());
        $this->assertFalse(StoryStatus::PENDING_REVIEW->canPublish());
        $this->assertTrue(StoryStatus::PENDING_REVIEW->canReject());
        $this->assertFalse(StoryStatus::PUBLISHED->canReject());
    }

    #[Test]
    public function story_evidence_type_returns_correct_allowed_mimes()
    {
        $this->assertContains('jpg', StoryEvidenceType::PHOTO->allowedMimes());
        $this->assertContains('mp4', StoryEvidenceType::VIDEO->allowedMimes());
        $this->assertNotContains('php', StoryEvidenceType::PHOTO->allowedMimes());
    }

    #[Test]
    public function story_evidence_type_has_max_size_for_videos()
    {
        $this->assertEquals(100, StoryEvidenceType::VIDEO->maxSizeMB());
        $this->assertEquals(10, StoryEvidenceType::PHOTO->maxSizeMB());
    }

    #[Test]
    public function complaint_evidence_category_has_labels_and_icons()
    {
        $this->assertEquals('Initial Report', ComplaintEvidenceCategory::INITIAL_REPORT->label());
        $this->assertNotEmpty(ComplaintEvidenceCategory::WITNESS_STATEMENT->icon());
        $this->assertNotEmpty(ComplaintEvidenceCategory::RESOLUTION_PROOF->description());
    }

    // =========================================================================
    // SUCCESS STORY MODEL
    // =========================================================================

    #[Test]
    public function success_story_has_enhanced_fillable_fields()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id'           => $this->candidate->id,
            'story_type'             => StoryType::EMPLOYMENT->value,
            'headline'               => 'Found a great job',
            'employer_name'          => 'Saudi Corp',
            'position_achieved'      => 'Senior Electrician',
            'salary_achieved'        => 3500.00,
            'salary_currency'        => 'SAR',
            'time_to_employment_days' => 45,
            'status'                 => StoryStatus::DRAFT->value,
        ]);

        $this->assertEquals(StoryType::EMPLOYMENT, $story->story_type);
        $this->assertEquals(StoryStatus::DRAFT, $story->status);
        $this->assertEquals('Found a great job', $story->headline);
        $this->assertEquals(3500.00, $story->salary_achieved);
    }

    #[Test]
    public function success_story_submit_for_review_updates_status()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::DRAFT->value,
        ]);

        $this->actingAs($this->admin);
        $story->submitForReview();

        $this->assertEquals(StoryStatus::PENDING_REVIEW, $story->fresh()->status);
    }

    #[Test]
    public function success_story_approve_sets_approved_by_and_at()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PENDING_REVIEW->value,
        ]);

        $this->actingAs($this->admin);
        $story->approve();

        $fresh = $story->fresh();
        $this->assertEquals(StoryStatus::APPROVED, $fresh->status);
        $this->assertEquals($this->admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    #[Test]
    public function success_story_publish_sets_published_at()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::APPROVED->value,
        ]);

        $this->actingAs($this->admin);
        $story->publish();

        $this->assertEquals(StoryStatus::PUBLISHED, $story->fresh()->status);
        $this->assertNotNull($story->fresh()->published_at);
    }

    #[Test]
    public function success_story_reject_sets_reason()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PENDING_REVIEW->value,
        ]);

        $this->actingAs($this->admin);
        $story->reject('Missing employment contract');

        $this->assertEquals(StoryStatus::REJECTED, $story->fresh()->status);
        $this->assertEquals('Missing employment contract', $story->fresh()->rejection_reason);
    }

    #[Test]
    public function success_story_increment_views()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'views_count'  => 5,
        ]);

        $story->incrementViews();

        $this->assertEquals(6, $story->fresh()->views_count);
    }

    #[Test]
    public function success_story_published_scope_returns_only_published()
    {
        SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PUBLISHED->value,
        ]);
        SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::DRAFT->value,
        ]);

        $this->assertEquals(1, SuccessStory::published()->count());
    }

    // =========================================================================
    // SUCCESS STORY EVIDENCE MODEL
    // =========================================================================

    #[Test]
    public function success_story_evidence_belongs_to_story()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);
        $evidence = SuccessStoryEvidence::factory()->create([
            'success_story_id' => $story->id,
            'uploaded_by'      => $this->admin->id,
        ]);

        $this->assertEquals($story->id, $evidence->successStory->id);
    }

    #[Test]
    public function success_story_evidence_formats_file_size()
    {
        $story    = SuccessStory::factory()->create(['candidate_id' => $this->candidate->id]);
        $evidence = SuccessStoryEvidence::factory()->create([
            'success_story_id' => $story->id,
            'file_size'        => 1024 * 1024, // 1 MB
            'uploaded_by'      => $this->admin->id,
        ]);

        $this->assertEquals('1 MB', $evidence->formatted_file_size);
    }

    // =========================================================================
    // COMPLAINT EVIDENCE ENHANCED
    // =========================================================================

    #[Test]
    public function complaint_evidence_has_evidence_category_cast()
    {
        $evidence = ComplaintEvidence::factory()->create([
            'complaint_id'      => $this->complaint->id,
            'evidence_category' => ComplaintEvidenceCategory::INITIAL_REPORT->value,
        ]);

        $this->assertEquals(
            ComplaintEvidenceCategory::INITIAL_REPORT,
            $evidence->fresh()->evidence_category
        );
    }

    #[Test]
    public function complaint_evidence_verify_method_updates_fields()
    {
        $evidence = ComplaintEvidence::factory()->create([
            'complaint_id' => $this->complaint->id,
            'verified'     => false,
        ]);

        $this->actingAs($this->admin);
        $evidence->verify();

        $fresh = $evidence->fresh();
        $this->assertTrue($fresh->verified);
        $this->assertEquals($this->admin->id, $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);
    }

    // =========================================================================
    // COMPLAINT TEMPLATE MODEL
    // =========================================================================

    #[Test]
    public function complaint_template_active_scope_works()
    {
        ComplaintTemplate::factory()->create(['is_active' => true]);
        ComplaintTemplate::factory()->create(['is_active' => false]);

        $this->assertEquals(1, ComplaintTemplate::active()->count());
    }

    #[Test]
    public function complaint_template_has_json_casts()
    {
        $template = ComplaintTemplate::factory()->create([
            'required_evidence_types' => ['contract', 'payslip'],
            'suggested_actions'       => ['Contact HR', 'Review contract'],
        ]);

        $this->assertIsArray($template->fresh()->required_evidence_types);
        $this->assertIsArray($template->fresh()->suggested_actions);
        $this->assertContains('contract', $template->fresh()->required_evidence_types);
    }

    // =========================================================================
    // COMPLAINT CONTROLLER ENHANCEMENTS
    // =========================================================================

    #[Test]
    public function admin_can_view_complaint_templates()
    {
        ComplaintTemplate::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->get('/complaints/templates');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.templates');
        $response->assertViewHas('templates');
    }

    #[Test]
    public function admin_can_view_enhanced_dashboard()
    {
        $response = $this->actingAs($this->admin)->get('/complaints/enhanced-dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.enhanced-dashboard');
    }

    #[Test]
    public function admin_can_add_categorized_evidence()
    {
        $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post("/complaints/{$this->complaint->id}/evidence/categorized", [
                'file'              => $file,
                'evidence_category' => ComplaintEvidenceCategory::INITIAL_REPORT->value,
                'description'       => 'Original incident report',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('complaint_evidence', [
            'complaint_id'      => $this->complaint->id,
            'evidence_category' => ComplaintEvidenceCategory::INITIAL_REPORT->value,
        ]);
    }

    #[Test]
    public function admin_can_verify_complaint_evidence()
    {
        $evidence = ComplaintEvidence::factory()->create([
            'complaint_id' => $this->complaint->id,
            'verified'     => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/complaints/evidence/{$evidence->id}/verify");

        $response->assertRedirect();
        $this->assertTrue($evidence->fresh()->verified);
    }

    #[Test]
    public function admin_can_create_complaint_from_template()
    {
        $template = ComplaintTemplate::factory()->create([
            'category'        => 'salary',
            'default_priority' => 'high',
            'is_active'       => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/complaints/from-template/{$template->id}", [
                'candidate_id' => $this->candidate->id,
                'description'  => 'My salary has not been paid for two months. Expected: 3000 SAR, Received: 0.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('complaints', [
            'candidate_id'      => $this->candidate->id,
            'complaint_category' => 'salary',
        ]);
    }
}
