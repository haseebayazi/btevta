<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Enums\StoryStatus;
use App\Enums\StoryType;
use App\Models\Candidate;
use App\Models\Country;
use App\Models\SuccessStory;
use App\Models\SuccessStoryEvidence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuccessStoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->admin     = User::factory()->create(['role' => 'super_admin']);
        $this->candidate = Candidate::factory()->create();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    #[Test]
    public function admin_can_view_success_stories_index()
    {
        $response = $this->actingAs($this->admin)->get('/admin/success-stories');

        $response->assertStatus(200);
        $response->assertViewIs('admin.success-stories.index');
    }

    #[Test]
    public function index_shows_list_of_stories()
    {
        SuccessStory::factory()->count(3)->create(['candidate_id' => $this->candidate->id]);

        $response = $this->actingAs($this->admin)->get('/admin/success-stories');

        $response->assertStatus(200);
        $response->assertViewHas('stories');
    }

    #[Test]
    public function index_filters_by_story_type()
    {
        SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'story_type'   => StoryType::EMPLOYMENT->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/success-stories?story_type=employment');

        $response->assertStatus(200);
    }

    #[Test]
    public function index_filters_by_status()
    {
        SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PUBLISHED->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/success-stories?status=published');

        $response->assertStatus(200);
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    #[Test]
    public function admin_can_view_create_form()
    {
        $response = $this->actingAs($this->admin)->get('/admin/success-stories/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.success-stories.create');
    }

    #[Test]
    public function admin_can_create_success_story()
    {
        $response = $this->actingAs($this->admin)->post('/admin/success-stories', [
            'candidate_id' => $this->candidate->id,
            'story_type'   => StoryType::EMPLOYMENT->value,
            'headline'     => 'A Great Success',
            'written_note' => 'This candidate achieved great things overseas.',
            'employer_name' => 'Saudi Co Ltd',
            'is_featured'  => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('success_stories', [
            'candidate_id' => $this->candidate->id,
            'headline'     => 'A Great Success',
            'status'       => StoryStatus::DRAFT->value,
        ]);
    }

    #[Test]
    public function creating_story_requires_candidate_id_and_written_note()
    {
        $response = $this->actingAs($this->admin)->post('/admin/success-stories', []);

        $response->assertSessionHasErrors(['candidate_id', 'written_note']);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    #[Test]
    public function admin_can_view_story_detail()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/success-stories/{$story->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.success-stories.show');
    }

    #[Test]
    public function viewing_story_increments_view_count()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'views_count'  => 0,
        ]);

        $this->actingAs($this->admin)->get("/admin/success-stories/{$story->id}");

        $this->assertEquals(1, $story->fresh()->views_count);
    }

    // =========================================================================
    // EDIT / UPDATE
    // =========================================================================

    #[Test]
    public function admin_can_edit_success_story()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/success-stories/{$story->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.success-stories.edit');
    }

    #[Test]
    public function admin_can_update_success_story()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/success-stories/{$story->id}", [
                'written_note'  => 'Updated story content.',
                'headline'      => 'Updated Headline',
                'employer_name' => 'New Corp',
                'is_featured'   => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('success_stories', [
            'id'       => $story->id,
            'headline' => 'Updated Headline',
        ]);
    }

    // =========================================================================
    // WORKFLOW
    // =========================================================================

    #[Test]
    public function draft_story_can_be_submitted_for_review()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::DRAFT->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/submit-review");

        $response->assertRedirect();
        $this->assertEquals(StoryStatus::PENDING_REVIEW, $story->fresh()->status);
    }

    #[Test]
    public function pending_story_can_be_approved()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/approve");

        $response->assertRedirect();
        $this->assertEquals(StoryStatus::APPROVED, $story->fresh()->status);
        $this->assertNotNull($story->fresh()->approved_by);
    }

    #[Test]
    public function approved_story_can_be_published()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/publish");

        $response->assertRedirect();
        $this->assertEquals(StoryStatus::PUBLISHED, $story->fresh()->status);
        $this->assertNotNull($story->fresh()->published_at);
    }

    #[Test]
    public function draft_story_cannot_be_published_directly()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::DRAFT->value,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/publish");

        // Status should remain draft
        $this->assertEquals(StoryStatus::DRAFT, $story->fresh()->status);
    }

    #[Test]
    public function pending_story_can_be_rejected()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/reject", [
                'reason' => 'Insufficient evidence provided.',
            ]);

        $response->assertRedirect();
        $this->assertEquals(StoryStatus::REJECTED, $story->fresh()->status);
        $this->assertEquals('Insufficient evidence provided.', $story->fresh()->rejection_reason);
    }

    // =========================================================================
    // EVIDENCE
    // =========================================================================

    #[Test]
    public function admin_can_add_photo_evidence()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 400, 400);

        $response = $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/evidence", [
                'evidence_type' => 'photo',
                'title'         => 'Work photo',
                'file'          => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('success_story_evidence', [
            'success_story_id' => $story->id,
            'evidence_type'    => 'photo',
            'title'            => 'Work photo',
        ]);
    }

    #[Test]
    public function evidence_upload_rejects_wrong_mime_type()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        // Uploading a PHP file disguised as photo
        $file = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $response = $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/evidence", [
                'evidence_type' => 'photo',
                'title'         => 'Malicious',
                'file'          => $file,
            ]);

        // Should fail with error (wrong extension for photo type)
        $this->assertDatabaseMissing('success_story_evidence', [
            'success_story_id' => $story->id,
        ]);
    }

    // =========================================================================
    // TOGGLE FEATURED
    // =========================================================================

    #[Test]
    public function admin_can_toggle_featured_status()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'is_featured'  => false,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/success-stories/{$story->id}/toggle-featured");

        $this->assertTrue($story->fresh()->is_featured);
    }

    // =========================================================================
    // PUBLIC GALLERY
    // =========================================================================

    #[Test]
    public function public_gallery_shows_published_stories_only()
    {
        SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
        SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status'       => StoryStatus::DRAFT->value,
        ]);

        $response = $this->get('/stories/gallery');

        $response->assertStatus(200);
        $response->assertViewHas('stories', function ($stories) {
            return $stories->count() === 1;
        });
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    #[Test]
    public function admin_can_delete_success_story()
    {
        $story = SuccessStory::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/admin/success-stories/{$story->id}");

        $response->assertRedirect(route('admin.success-stories.index'));
        $this->assertSoftDeleted('success_stories', ['id' => $story->id]);
    }
}
