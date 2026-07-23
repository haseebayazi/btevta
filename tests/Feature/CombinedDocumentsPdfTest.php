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
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
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
            ->store('pre-departure-documents/'.$this->candidate->id, 'private');
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
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Passport scan');
        $pdfPath = 'pre-departure-documents/'.$this->candidate->id.'/passport.pdf';
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
    public function it_embeds_a_modern_compressed_pdf_instead_of_a_placeholder(): void
    {
        // Scanners, phone apps and office suites emit PDFs using compressed
        // cross-reference / object streams that the free FPDI parser cannot read.
        // Such documents used to collapse into a "could not be embedded"
        // placeholder page; they must now be embedded with all their real pages.
        $passport = DocumentChecklist::create([
            'name' => 'Passport', 'code' => 'passport', 'category' => 'mandatory',
            'is_mandatory' => true, 'display_order' => 2, 'is_active' => true,
        ]);

        $pdfPath = 'pre-departure-documents/'.$this->candidate->id.'/passport.pdf';
        Storage::disk('private')->put($pdfPath, $this->modernObjectStreamPdf(2));
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
        $body = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $body);

        // Cover page + the two embedded passport pages = 3. A placeholder
        // fallback would have produced only 2 pages (cover + 1 placeholder).
        $reader = new Fpdi;
        $this->assertSame(3, $reader->setSourceFile(StreamReader::createByString($body)));
    }

    #[Test]
    public function it_still_produces_a_pdf_when_candidate_has_no_documents(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reports.pre-departure.combined-documents', $this->candidate));

        $response->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * Build a multi-page PDF whose structural objects live inside an object
     * stream behind a cross-reference stream — the exact layout the free FPDI
     * parser rejects.
     */
    private function modernObjectStreamPdf(int $pages): string
    {
        $pdf = "%PDF-1.6\n%\xE2\xE3\xCF\xD3\n";

        // Content streams must stay top-level (streams cannot live in an ObjStm).
        $contentNums = [];
        for ($p = 0; $p < $pages; $p++) {
            $num = 200 + $p;
            $content = 'BT /F1 24 Tf 40 150 Td (Passport page '.($p + 1).') Tj ET';
            $pdf .= "$num 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream\nendobj\n";
            $contentNums[$p] = $num;
        }

        $compressed = [];
        $kids = [];
        for ($p = 0; $p < $pages; $p++) {
            $pageNum = 10 + $p;
            $kids[] = "$pageNum 0 R";
            $compressed[$pageNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Contents {$contentNums[$p]} 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
        }
        $compressed[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $compressed[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids)."] /Count $pages >>";
        $compressed[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        ksort($compressed);

        $head = '';
        $body = '';
        $off = 0;
        foreach ($compressed as $num => $data) {
            $head .= "$num $off ";
            $chunk = $data.' ';
            $body .= $chunk;
            $off += strlen($chunk);
        }
        $first = strlen($head);
        $objStm = gzcompress($head.$body);

        $n = count($compressed);
        $pdf .= "50 0 obj\n<< /Type /ObjStm /N $n /First $first /Filter /FlateDecode /Length ".strlen($objStm)." >>\nstream\n".$objStm."\nendstream\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "60 0 obj\n<< /Type /XRef /Size 61 /Root 1 0 R /W [1 4 2] /Index [0 61] /Length 4 >>\nstream\n\x00\x00\x00\x00\nendstream\nendobj\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF\n";

        return $pdf;
    }
}
