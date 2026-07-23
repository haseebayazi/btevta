<?php

namespace Tests\Unit;

use App\Services\CandidateDocumentPackageService;
use App\Services\Pdf\PdfNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Guards the fix for the combined-documents download bug: modern PDFs
 * (compressed cross-reference / object streams) produced by scanners and phone
 * apps could not be embedded by the free FPDI parser, so every non-image page
 * turned into a "could not be embedded" placeholder. PdfNormalizer rewrites
 * such PDFs into a classic structure FPDI can read.
 */
class PdfNormalizerTest extends TestCase
{
    private PdfNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new PdfNormalizer;
    }

    #[Test]
    public function free_fpdi_parser_rejects_a_cross_reference_stream_pdf(): void
    {
        // This is the exact failure users hit; if FPDI is ever upgraded to a
        // parser that handles it natively, this test documents the assumption.
        $this->expectException(CrossReferenceException::class);

        $pdf = new Fpdi;
        $pdf->setSourceFile(StreamReader::createByString($this->xrefStreamPdf()));
    }

    #[Test]
    public function it_normalizes_a_cross_reference_stream_pdf_so_fpdi_can_embed_it(): void
    {
        $normalized = $this->normalizer->normalize($this->xrefStreamPdf());

        $this->assertNotNull($normalized, 'Normalizer should handle xref-stream PDFs.');
        $this->assertSame(1, $this->importablePageCount($normalized));
    }

    #[Test]
    public function it_normalizes_an_object_stream_pdf(): void
    {
        $normalized = $this->normalizer->normalize($this->objectStreamPdf());

        $this->assertNotNull($normalized);
        $this->assertSame(1, $this->importablePageCount($normalized));
    }

    #[Test]
    public function it_preserves_embedded_images_across_multiple_pages(): void
    {
        $normalized = $this->normalizer->normalize($this->scannedPdf(3));

        $this->assertNotNull($normalized);
        $this->assertSame(3, $this->importablePageCount($normalized));
        $this->assertStringContainsString('/DCTDecode', $normalized, 'Embedded JPEG must survive normalisation.');
    }

    #[Test]
    public function it_handles_object_streams_compressed_with_a_png_predictor(): void
    {
        $normalized = $this->normalizer->normalize($this->scannedPdf(2, true));

        $this->assertNotNull($normalized);
        $this->assertSame(2, $this->importablePageCount($normalized));
    }

    #[Test]
    public function it_returns_null_for_content_it_cannot_safely_rewrite(): void
    {
        $encrypted = str_replace('/Root 1 0 R', '/Root 1 0 R /Encrypt 9 0 R', $this->objectStreamPdf());

        $this->assertNull($this->normalizer->normalize('not a pdf at all'));
        $this->assertNull($this->normalizer->normalize("%PDF-1.7\ngarbage\n%%EOF"));
        $this->assertNull($this->normalizer->normalize(substr($this->scannedPdf(1), 0, 1500)));
        $this->assertNull($this->normalizer->normalize($encrypted), 'Encrypted PDFs must not be rewritten.');
    }

    #[Test]
    public function the_package_service_embeds_a_modern_pdf_instead_of_a_placeholder(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pkg').'.pdf';
        file_put_contents($tmp, $this->scannedPdf(2));

        try {
            $service = new CandidateDocumentPackageService;
            $pdf = new Fpdi('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetCompression(false); // keep placeholder text greppable

            $append = (new \ReflectionClass($service))->getMethod('appendFile');
            $append->setAccessible(true);
            $append->invoke($service, $pdf, 'CNIC', $tmp);

            $out = $pdf->Output('S');

            $this->assertStringNotContainsString('could not be embedded', $out);
            $this->assertSame(2, $this->importablePageCount($out));
        } finally {
            @unlink($tmp);
        }
    }

    // ---- Helpers: build in-memory "modern" PDFs -------------------------

    private function importablePageCount(string $pdf): int
    {
        $reader = new Fpdi;

        return $reader->setSourceFile(StreamReader::createByString($pdf));
    }

    private function xrefStreamPdf(): string
    {
        $entry = fn (int $t, int $f2, int $f3) => chr($t).pack('N', $f2).chr($f3);

        $header = "%PDF-1.5\n%\xE2\xE3\xCF\xD3\n";
        $objs = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        ];
        $content = 'BT /F1 24 Tf 40 150 Td (Hello) Tj ET';
        $objs[4] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
        $objs[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = $header;
        $offsets = [];
        foreach ($objs as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }

        $xrefNum = 6;
        $xrefOffset = strlen($pdf);
        $entries = $entry(0, 0, 255);
        for ($i = 1; $i <= 5; $i++) {
            $entries .= $entry(1, $offsets[$i], 0);
        }
        $entries .= $entry(1, $xrefOffset, 0);
        $compressed = gzcompress($entries);
        $dict = '<< /Type /XRef /Size 7 /Root 1 0 R /W [1 4 1] /Index [0 7] /Filter /FlateDecode /Length '.strlen($compressed).' >>';
        $pdf .= "$xrefNum 0 obj\n$dict\nstream\n".$compressed."\nendstream\nendobj\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF\n";

        return $pdf;
    }

    private function objectStreamPdf(): string
    {
        $compressedObjs = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $head = '';
        $body = '';
        $off = 0;
        foreach ($compressedObjs as $num => $data) {
            $head .= "$num $off ";
            $chunk = $data.' ';
            $body .= $chunk;
            $off += strlen($chunk);
        }
        $first = strlen($head);
        $objStm = gzcompress($head.$body);

        $pdf = "%PDF-1.5\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        $offsets[7] = strlen($pdf);
        $n = count($compressedObjs);
        $pdf .= "7 0 obj\n<< /Type /ObjStm /N $n /First $first /Filter /FlateDecode /Length ".strlen($objStm)." >>\nstream\n".$objStm."\nendstream\nendobj\n";

        $content = 'BT /F1 24 Tf 40 150 Td (Hello) Tj ET';
        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "8 0 obj\n<< /Type /XRef /Size 9 /Root 1 0 R /W [1 4 2] /Index [0 9] /Length 4 >>\nstream\n\x00\x00\x00\x00\nendstream\nendobj\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF\n";

        return $pdf;
    }

    private function scannedPdf(int $pages, bool $predictor = false): string
    {
        $jpeg = $this->sampleJpeg();
        [$iw, $ih] = getimagesizefromstring($jpeg);

        $pdf = "%PDF-1.6\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        $imgNum = 100;
        $offsets[$imgNum] = strlen($pdf);
        $pdf .= "$imgNum 0 obj\n<< /Type /XObject /Subtype /Image /Width $iw /Height $ih /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream\nendobj\n";

        $contentNums = [];
        for ($p = 0; $p < $pages; $p++) {
            $num = 200 + $p;
            $content = 'q 300 0 0 300 20 20 cm /Im1 Do Q';
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream\nendobj\n";
            $contentNums[$p] = $num;
        }

        $compressed = [];
        $kids = [];
        for ($p = 0; $p < $pages; $p++) {
            $pageNum = 10 + $p;
            $kids[] = "$pageNum 0 R";
            $compressed[$pageNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 340 340] /Contents {$contentNums[$p]} 0 R /Resources << /XObject << /Im1 $imgNum 0 R >> >> >>";
        }
        $compressed[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $compressed[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids)."] /Count $pages >>";
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
        $payload = $head.$body;

        $decodeParms = '';
        if ($predictor) {
            $rowLen = 32;
            $payload = str_pad($payload, (int) (ceil(strlen($payload) / $rowLen) * $rowLen), ' ');
            $payload = $this->pngUpEncode($payload, $rowLen);
            $decodeParms = " /DecodeParms << /Predictor 12 /Columns $rowLen >>";
        }
        $objStm = gzcompress($payload);

        $offsets[50] = strlen($pdf);
        $n = count($compressed);
        $pdf .= "50 0 obj\n<< /Type /ObjStm /N $n /First $first /Filter /FlateDecode$decodeParms /Length ".strlen($objStm)." >>\nstream\n".$objStm."\nendstream\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "60 0 obj\n<< /Type /XRef /Size 61 /Root 1 0 R /W [1 4 2] /Index [0 61] /Length 4 >>\nstream\n\x00\x00\x00\x00\nendstream\nendobj\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF\n";

        return $pdf;
    }

    private function pngUpEncode(string $data, int $rowLen): string
    {
        $rows = str_split($data, $rowLen);
        $out = '';
        $prev = str_repeat("\x00", $rowLen);
        foreach ($rows as $row) {
            $row = str_pad($row, $rowLen, "\x00");
            $enc = '';
            for ($i = 0; $i < $rowLen; $i++) {
                $enc .= chr((ord($row[$i]) - ord($prev[$i])) & 0xFF);
            }
            $out .= "\x02".$enc;
            $prev = $row;
        }

        return $out;
    }

    private function sampleJpeg(): string
    {
        $im = imagecreatetruecolor(200, 200);
        imagefill($im, 0, 0, imagecolorallocate($im, 210, 225, 245));
        imagefilledrectangle($im, 20, 20, 180, 60, imagecolorallocate($im, 20, 40, 120));
        ob_start();
        imagejpeg($im, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($im);

        return $jpeg;
    }
}
