<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CombinedDocumentsPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');

        $campus = Campus::factory()->create(['name' => 'Test Campus', 'code' => 'CAMP-T']);

        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->candidate = Candidate::factory()->create([
            'status' => 'listed',
            'campus_id' => $campus->id,
        ]);
    }

    #[Test]
    public function it_streams_a_valid_combined_pdf_merging_images_and_pdfs(): void
    {
        // Image document (CNIC)
        $cnic = DocumentChecklist::create([
            'name' => 'CNIC Front & Back', 'code' => 'cnic', 'category' => 'mandatory',
            'is_mandatory' => true, 'display_order' => 1, 'is_active' => true,
        ]);
        $imagePath = UploadedFile::fake()->image('cnic.jpg', 600, 400)
            ->store('pre-departure-documents/' . $this->candidate->id, 'private');
        PreDepartureDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $cnic->id,
            'file_path' => $imagePath,
            'original_filename' => 'cnic.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1000,
            'uploaded_at' => now(),
            'uploaded_by' => $this->admin->id,
        ]);

        // PDF document (Passport)
        $passport = DocumentChecklist::create([
            'name' => 'Passport', 'code' => 'passport', 'category' => 'mandatory',
            'is_mandatory' => true, 'display_order' => 2, 'is_active' => true,
        ]);
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Passport scan');
        $pdfPath = 'pre-departure-documents/' . $this->candidate->id . '/passport.pdf';
        Storage::disk('private')->put($pdfPath, $pdf->Output('S'));
        PreDepartureDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $passport->id,
            'file_path' => $pdfPath,
            'original_filename' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'uploaded_at' => now(),
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('reports.pre-departure.combined-documents', $this->candidate));

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $body = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $body);
        // cover + image page + imported passport page => at least 3 page objects
        $this->assertGreaterThanOrEqual(3, substr_count($body, '/Type /Page'));
    }

    #[Test]
    public function it_still_produces_a_pdf_when_candidate_has_no_documents(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.pre-departure.combined-documents', $this->candidate));

        $response->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
