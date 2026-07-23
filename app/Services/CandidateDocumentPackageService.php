<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\DocumentArchive;
use App\Services\Pdf\PdfNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Builds a single, consolidated PDF containing every document collected for a
 * candidate across the whole lifecycle (pre-departure, visa processing,
 * departure and post-departure) in a fixed, human-friendly order.
 *
 * The engine merges native PDF files (via FPDI) and images (JPG/PNG, via FPDF)
 * into one document. It never mutates any stored data and degrades gracefully:
 * any document that cannot be read/embedded is replaced by a small placeholder
 * page so a single bad file never breaks the whole package.
 */
class CandidateDocumentPackageService
{
    /**
     * Disks to search when resolving a stored relative path to an absolute one.
     * Order matters only for performance; a path lives on exactly one disk.
     */
    protected array $disks = ['private', 'public', 'local', 'photos'];

    /**
     * Rewrites modern PDFs (compressed cross-reference / object streams) into a
     * form the free FPDI parser can embed.
     */
    protected PdfNormalizer $pdfNormalizer;

    public function __construct(?PdfNormalizer $pdfNormalizer = null)
    {
        $this->pdfNormalizer = $pdfNormalizer ?? new PdfNormalizer;
    }

    /**
     * Generate the combined PDF and return its raw binary contents.
     *
     * The result is intentionally NOT persisted to disk (these are highly
     * sensitive documents); the caller should stream it directly.
     */
    public function generateCombinedPdf(Candidate $candidate): string
    {
        $sections = $this->collectDocuments($candidate);

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('LEAP - WASL');
        $pdf->SetAuthor('LEAP - WASL');
        $pdf->SetTitle('Candidate Document Package - '.($candidate->btevta_id ?? $candidate->id));

        $this->addCoverPage($pdf, $candidate, $sections);

        foreach ($sections as $section) {
            foreach ($section['files'] as $file) {
                $this->appendFile($pdf, $section['label'], $file);
            }
        }

        return $pdf->Output('S');
    }

    /**
     * A short, filesystem-safe filename for the download.
     */
    public function filename(Candidate $candidate): string
    {
        $id = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) ($candidate->btevta_id ?? $candidate->id));

        return sprintf('candidate-documents_%s_%s.pdf', $id, now()->format('Ymd_His'));
    }

    /**
     * Build the ordered list of document sections, keeping only the ones that
     * actually have at least one resolvable file for this candidate.
     *
     * @return array<int, array{label: string, files: array<int, string>}>
     */
    protected function collectDocuments(Candidate $candidate): array
    {
        $pre = $candidate->preDepartureDocuments()
            ->with(['documentChecklist', 'pages'])
            ->get();

        $visa = $candidate->visaProcess;
        $departure = $candidate->departure;
        $post = $candidate->postDepartureDetail;

        // Current versions from the central archive, keyed by document_type.
        $archive = DocumentArchive::query()
            ->where('candidate_id', $candidate->id)
            ->where('is_current_version', true)
            ->get()
            ->keyBy('document_type');

        $arc = fn (string $type) => $this->resolveMany([optional($archive->get($type))->file_path]);

        $slots = [
            // Pre-departure identity & core documents
            ['Photograph', $this->pick(
                $this->resolveMany([$candidate->photo_path]),
                $arc('photo'),
            )],
            ['CNIC', $this->pick(
                $this->preFiles($pre, 'cnic'),
                $arc('cnic'),
            )],
            ['Passport', $this->pick(
                $this->preFiles($pre, 'passport'),
                $arc('passport'),
            )],
            ['Family Registration Certificate (FRC)', $this->pick(
                $this->preFiles($pre, 'frc'),
            )],
            ['Police Character Certificate (PCC)', $this->pick(
                $this->preFiles($pre, 'pcc'),
                $arc('police_clearance'),
            )],
            ['Domicile', $this->pick(
                $this->preFiles($pre, 'domicile'),
                $arc('domicile'),
            )],
            ['Pre-Medical Reports', $this->pick(
                $this->preFiles($pre, 'pre_medical'),
            )],
            ['Other Certificates', $this->pick(
                array_merge(
                    $this->preFiles($pre, 'certifications'),
                    $this->preFiles($pre, 'other_mandatory'),
                    $this->preFiles($pre, 'professional_license'),
                ),
                $arc('education_certificate'),
            )],

            // Visa processing stage
            ['Takamol Certificate', $this->pick(
                $this->resolveMany([
                    optional($visa)->takamol_result_path,
                    optional($visa)->takamol_appointment_slip_path,
                ]),
                $arc('takamol_certificate'),
            )],
            ['Medical Report', $this->pick(
                $this->resolveMany([
                    optional($visa)->medical_result_path,
                    optional($visa)->medical_appointment_slip_path,
                ]),
                $this->resolveMany([optional($departure)->medical_report_path]),
                $arc('gamca_certificate'),
                $arc('medical_certificate'),
            )],
            ['Biometric Report', $this->pick(
                $this->resolveMany([optional($visa)->interview_evidence_path]),
                $arc('biometric'),
            )],
            ['Visa', $this->pick(
                $arc('visa_copy'),
            )],
            ['PTN', $this->pick(
                $this->resolveMany([optional($visa)->ptn_document_path]),
                $arc('ptn_document'),
            )],
            ['Protector', $this->pick(
                $this->resolveMany([optional($visa)->protector_document_path]),
            )],
            ['Air Ticket', $this->pick(
                $this->resolveMany([
                    optional($visa)->ticket_path,
                    optional($visa)->travel_plan_path,
                ]),
                $this->resolveMany([optional($departure)->ticket_path]),
                $arc('ticket'),
            )],

            // Post-departure stage
            ['Iqama Card', $this->pick(
                $this->resolveMany([optional($post)->iqama_evidence_path]),
                $this->resolveMany([optional($post)->residency_proof_path]),
                $arc('iqama'),
            )],
            ['Foreign Bank Account', $this->pick(
                $this->resolveMany([optional($post)->foreign_bank_evidence_path]),
            )],
            ['Qiwa Agreement', $this->pick(
                $this->resolveMany([
                    optional($post)->contract_evidence_path,
                    optional($post)->final_contract_path,
                ]),
                $arc('employment_contract'),
            )],
            ['Company Card', $this->pick(
                $this->resolveMany([optional($post)->wps_evidence_path]),
                $arc('company_card'),
            )],
            ['Salary Evidence', $this->pick(
                $this->resolveMany([optional($departure)->salary_proof_path]),
                $arc('salary_slip'),
            )],
        ];

        $sections = [];
        foreach ($slots as [$label, $files]) {
            if (! empty($files)) {
                $sections[] = ['label' => $label, 'files' => $files];
            }
        }

        return $sections;
    }

    /**
     * Return the first group of resolved files that is non-empty.
     *
     * @param  array<int, string>  ...$groups
     * @return array<int, string>
     */
    protected function pick(array ...$groups): array
    {
        foreach ($groups as $group) {
            if (! empty($group)) {
                return $group;
            }
        }

        return [];
    }

    /**
     * Resolve the main file + additional pages for a pre-departure checklist code.
     *
     * @return array<int, string>
     */
    protected function preFiles(Collection $pre, string $code): array
    {
        $doc = $pre->first(fn ($d) => optional($d->documentChecklist)->code === $code);

        if (! $doc) {
            return [];
        }

        $paths = [$doc->file_path];
        foreach ($doc->pages as $page) {
            $paths[] = $page->file_path;
        }

        return $this->resolveMany($paths);
    }

    /**
     * Resolve a list of (possibly null) stored paths to absolute paths that exist.
     *
     * @param  array<int, string|null>  $paths
     * @return array<int, string>
     */
    protected function resolveMany(array $paths): array
    {
        $resolved = [];
        foreach ($paths as $path) {
            $abs = $this->resolve($path);
            if ($abs !== null && ! in_array($abs, $resolved, true)) {
                $resolved[] = $abs;
            }
        }

        return $resolved;
    }

    /**
     * Resolve a single stored relative path to an absolute filesystem path.
     */
    protected function resolve(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Skip externally hosted assets (e.g. remote photo URLs).
        if (preg_match('#^https?://#i', $path)) {
            return null;
        }

        foreach ($this->disks as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->path($path);
                }
            } catch (\Throwable $e) {
                // Ignore misconfigured/missing disks and keep looking.
            }
        }

        // Already an absolute path on the local filesystem.
        if (is_file($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Append one file (PDF or image) to the package, falling back to a
     * placeholder page if it cannot be embedded.
     */
    protected function appendFile(Fpdi $pdf, string $label, string $absPath): void
    {
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));

        try {
            if ($ext === 'pdf') {
                $this->appendPdf($pdf, $absPath);
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $this->appendImage($pdf, $label, $absPath, $ext);
            } else {
                $this->appendPlaceholder($pdf, $label, basename($absPath), 'This file type cannot be embedded.');
            }
        } catch (\Throwable $e) {
            $this->appendPlaceholder(
                $pdf,
                $label,
                basename($absPath),
                'The original file could not be embedded in this package.'
            );
        }
    }

    /**
     * Import every page of an existing PDF at its native size/orientation.
     *
     * Modern PDFs (produced by scanners, phone apps and office suites) use
     * compressed cross-reference streams and object streams that the free FPDI
     * parser cannot read. When the direct import fails we transparently rewrite
     * the document into a classic-structured PDF and retry, so real content is
     * embedded instead of a "could not be embedded" placeholder.
     */
    protected function appendPdf(Fpdi $pdf, string $absPath): void
    {
        try {
            $pageCount = $pdf->setSourceFile($absPath);
        } catch (\Throwable $e) {
            $pageCount = $this->importNormalizedPdf($pdf, $absPath);
        }

        for ($p = 1; $p <= $pageCount; $p++) {
            $tpl = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($tpl);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
        }
    }

    /**
     * Rewrite an FPDI-incompatible PDF into a classic structure and set it as
     * the current source, returning its page count.
     *
     * @throws \RuntimeException when the file cannot be read or normalised.
     */
    protected function importNormalizedPdf(Fpdi $pdf, string $absPath): int
    {
        $raw = @file_get_contents($absPath);
        if ($raw === false) {
            throw new \RuntimeException('Unable to read PDF for normalisation.');
        }

        $normalized = $this->pdfNormalizer->normalize($raw);
        if ($normalized === null) {
            throw new \RuntimeException('PDF could not be normalised for embedding.');
        }

        return $pdf->setSourceFile(StreamReader::createByString($normalized));
    }

    /**
     * Place an image on a single A4 page, fitted within the margins.
     */
    protected function appendImage(Fpdi $pdf, string $label, string $absPath, string $ext): void
    {
        $pdf->AddPage('P', 'A4');
        $this->pageLabel($pdf, $label);

        $info = @getimagesize($absPath);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            throw new \RuntimeException('Unreadable image.');
        }

        [$imgW, $imgH] = $info;

        $margin = 12.0;
        $top = 24.0;
        $maxW = 210.0 - (2 * $margin);
        $maxH = 297.0 - $top - $margin;

        $ratio = $imgW / max($imgH, 1);
        $w = $maxW;
        $h = $w / $ratio;
        if ($h > $maxH) {
            $h = $maxH;
            $w = $h * $ratio;
        }

        $x = $margin + (($maxW - $w) / 2);
        $y = $top + (($maxH - $h) / 2);

        $type = $ext === 'png' ? 'PNG' : 'JPEG';
        $pdf->Image($absPath, $x, $y, $w, $h, $type);
    }

    /**
     * A page shown when a document exists but cannot be embedded.
     */
    protected function appendPlaceholder(Fpdi $pdf, string $label, string $filename, string $note): void
    {
        $pdf->AddPage('P', 'A4');
        $this->pageLabel($pdf, $label);

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetXY(12, 32);
        $pdf->MultiCell(186, 6, $this->latin($note."\n\nFile: ".$filename));
    }

    /**
     * Draw the section label header at the top of a generated page.
     */
    protected function pageLabel(Fpdi $pdf, string $label): void
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetXY(12, 12);
        $pdf->Cell(0, 8, $this->latin($label), 0, 1, 'L');

        $pdf->SetDrawColor(30, 64, 175);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(12, 21, 198, 21);

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Build the cover page with candidate details and a table of contents.
     *
     * @param  array<int, array{label: string, files: array<int, string>}>  $sections
     */
    protected function addCoverPage(Fpdi $pdf, Candidate $candidate, array $sections): void
    {
        $pdf->AddPage('P', 'A4');

        $pdf->SetFont('Arial', 'B', 22);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->Cell(0, 14, 'LEAP - WASL', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Cell(0, 6, $this->latin('Workforce Abroad Skills & Linkages (WASL) Program'), 0, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->Cell(0, 10, 'Candidate Document Package', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(8);

        $rows = [
            ['Candidate Name', $candidate->name],
            ['LEAP ID', $candidate->btevta_id],
            ['CNIC', $candidate->cnic],
            ['Campus', optional($candidate->campus)->name ?? 'N/A'],
            ['Trade', optional($candidate->trade)->name ?? 'N/A'],
            ['Current Status', ucwords(str_replace('_', ' ', (string) $candidate->status))],
        ];

        foreach ($rows as [$key, $value]) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(45, 8, $this->latin($key.':'), 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 8, $this->latin((string) ($value ?? 'N/A')), 0, 1);
        }

        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->Cell(0, 8, 'Included Documents', 0, 1);
        $pdf->SetTextColor(0, 0, 0);

        if (empty($sections)) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 6, $this->latin('No documents have been uploaded for this candidate yet.'), 0, 1);
        } else {
            $index = 1;
            foreach ($sections as $section) {
                $count = count($section['files']);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(10, 6, $index.'.', 0, 0);
                $pdf->Cell(130, 6, $this->latin($section['label']), 0, 0);
                $pdf->Cell(0, 6, $count.' file'.($count === 1 ? '' : 's'), 0, 1);
                $index++;
            }
        }

        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(
            0,
            6,
            $this->latin('Generated: '.now()->format('d M, Y h:i A').' by '.(optional(auth()->user())->name ?? 'System')),
            0,
            1
        );
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * FPDF renders text as Latin-1; transliterate UTF-8 safely to avoid errors.
     */
    protected function latin(string $text): string
    {
        $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        if ($out !== false) {
            return $out;
        }

        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }
}
