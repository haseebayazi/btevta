<?php

namespace App\Services\Pdf;

/**
 * Rewrites "modern" PDFs (PDF 1.5+) that use cross-reference streams and/or
 * object streams into a flat, classic cross-reference-table PDF.
 *
 * Why this exists
 * ---------------
 * The free parser shipped with FPDI cannot follow compressed cross-reference
 * streams (/Type /XRef) or read objects packed inside object streams
 * (/Type /ObjStm). Any PDF produced by a modern scanner, phone camera app or
 * office suite typically uses those structures, so FPDI throws a
 * CrossReferenceException and the document cannot be embedded in the combined
 * candidate package. Rasterising the PDF is not an option here because the
 * hosting environment has no Ghostscript/Imagick/qpdf available.
 *
 * This normaliser is a pure-PHP fallback that:
 *   1. Scans every top-level indirect object straight from the raw bytes.
 *   2. Expands objects stored inside object streams back into top-level ones.
 *   3. Re-emits everything with a classic `xref` table and trailer.
 *
 * It only touches the file *structure* — object bodies (including already
 * FlateDecode-compressed content streams, which FPDI reads happily) are copied
 * verbatim, so no image quality or fidelity is lost.
 *
 * It is intentionally conservative: anything it cannot confidently handle
 * (encryption, malformed structure, unsupported stream filters) makes it return
 * null so the caller can fall back to its existing placeholder behaviour. It
 * therefore never makes a currently-working document worse.
 */
class PdfNormalizer
{
    /**
     * Attempt to rewrite the given raw PDF into an FPDI-compatible one.
     *
     * @return string|null Normalised PDF bytes, or null if it cannot be handled.
     */
    public function normalize(string $raw): ?string
    {
        if (strncmp($raw, '%PDF-', 5) !== 0) {
            return null;
        }

        try {
            // Encrypted documents cannot be rewritten without the password.
            if ($this->looksEncrypted($raw)) {
                return null;
            }

            $objects = $this->scanTopLevelObjects($raw);
            if (empty($objects)) {
                return null;
            }

            $trailer = $this->collectTrailer($raw, $objects);
            if ($trailer === null || empty($trailer['root'])) {
                return null;
            }

            // Pull objects out of any object streams into the flat object map.
            $flat = [];
            foreach ($objects as $num => $obj) {
                $type = $this->dictGetName($obj['dict'], 'Type');
                if ($type === 'ObjStm' || $type === 'XRef') {
                    // These structural objects are rebuilt / no longer needed.
                    continue;
                }
                $flat[$num] = $obj['inner'];
            }

            foreach ($objects as $obj) {
                if ($this->dictGetName($obj['dict'], 'Type') !== 'ObjStm') {
                    continue;
                }
                $expanded = $this->expandObjectStream($raw, $obj, $objects);
                if ($expanded === null) {
                    return null; // Could not decode a needed object stream.
                }
                foreach ($expanded as $num => $body) {
                    // A real top-level object always wins over a compressed copy.
                    if (! array_key_exists($num, $flat)) {
                        $flat[$num] = $body;
                    }
                }
            }

            if (empty($flat)) {
                return null;
            }

            return $this->assemble($flat, $trailer);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Heuristically detect an encrypted document. We only look inside trailer /
     * cross-reference dictionaries so a literal "/Encrypt" appearing in a
     * content stream does not trigger a false positive.
     */
    protected function looksEncrypted(string $raw): bool
    {
        if (preg_match('/trailer\b/', $raw)) {
            if (preg_match('/trailer\s*<<(.*?)>>/s', $raw, $m) && strpos($m[1], '/Encrypt') !== false) {
                return true;
            }
        }

        // Cross-reference stream dictionaries carrying /Encrypt.
        if (preg_match_all('/<<(?:[^<>]|<<[^<>]*>>)*\/Type\s*\/XRef(?:[^<>]|<<[^<>]*>>)*>>/s', $raw, $mm)) {
            foreach ($mm[0] as $dict) {
                if (strpos($dict, '/Encrypt') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Walk the raw bytes and capture every top-level indirect object.
     *
     * Streams are skipped over so binary payloads that happen to contain the
     * bytes "obj"/"endobj" cannot corrupt the scan. Later definitions of the
     * same object number win (incremental-update semantics).
     *
     * @return array<int, array{inner: string, dict: string}>
     */
    protected function scanTopLevelObjects(string $raw): array
    {
        $objects = [];
        $len = strlen($raw);
        $pos = 0;

        while ($pos < $len
            && preg_match('/(\d+)\s+(\d+)\s+obj\b/', $raw, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $num = (int) $m[1][0];
            $afterObj = $m[0][1] + strlen($m[0][0]);

            $endobj = strpos($raw, 'endobj', $afterObj);

            // Detect a stream keyword ("stream", not "endstream") before endobj.
            $streamPos = false;
            if (preg_match('/(?<!end)stream(?:\r\n|\r|\n)/', $raw, $sm, PREG_OFFSET_CAPTURE, $afterObj)) {
                $streamPos = $sm[0][1];
            }

            if ($streamPos !== false && ($endobj === false || $streamPos < $endobj)) {
                $endstream = strpos($raw, 'endstream', $streamPos);
                if ($endstream === false) {
                    break;
                }
                $endobj = strpos($raw, 'endobj', $endstream);
            }

            if ($endobj === false) {
                break;
            }

            $inner = trim(substr($raw, $afterObj, $endobj - $afterObj));
            $objects[$num] = [
                'inner' => $inner,
                'dict' => $this->extractLeadingDict($inner),
            ];

            $pos = $endobj + 6; // strlen('endobj')
        }

        return $objects;
    }

    /**
     * Gather the trailer information (Root/Info/Size) from either a classic
     * trailer dictionary or one or more cross-reference stream dictionaries.
     *
     * @param  array<int, array{inner: string, dict: string}>  $objects
     * @return array{root: string, info: ?string}|null
     */
    protected function collectTrailer(string $raw, array $objects): ?array
    {
        $root = null;
        $info = null;

        // Prefer an explicit classic trailer if present (hybrid files).
        if (preg_match_all('/trailer\s*(<<.*?>>)/s', $raw, $tm)) {
            foreach ($tm[1] as $dict) {
                $root = $root ?? $this->dictGetRef($dict, 'Root');
                $info = $info ?? $this->dictGetRef($dict, 'Info');
            }
        }

        // Cross-reference stream dictionaries.
        foreach ($objects as $obj) {
            if ($this->dictGetName($obj['dict'], 'Type') !== 'XRef') {
                continue;
            }
            $root = $root ?? $this->dictGetRef($obj['dict'], 'Root');
            $info = $info ?? $this->dictGetRef($obj['dict'], 'Info');
        }

        if ($root === null) {
            return null;
        }

        return ['root' => $root, 'info' => $info];
    }

    /**
     * Decode an object stream and return the objects it carries.
     *
     * @param  array{inner: string, dict: string}  $obj
     * @param  array<int, array{inner: string, dict: string}>  $objects
     * @return array<int, string>|null objNum => object body, or null on failure
     */
    protected function expandObjectStream(string $raw, array $obj, array $objects): ?array
    {
        $dict = $obj['dict'];
        $n = $this->dictGetInt($dict, 'N');
        $first = $this->dictGetInt($dict, 'First');
        if ($n === null || $first === null) {
            return null;
        }

        $data = $this->getStreamData($obj['inner'], $objects);
        if ($data === null) {
            return null;
        }

        $decoded = $this->decodeStream($data, $dict, $objects);
        if ($decoded === null) {
            return null;
        }

        $header = substr($decoded, 0, $first);
        if (! preg_match_all('/(\d+)\s+(\d+)/', $header, $hm, PREG_SET_ORDER)) {
            return null;
        }

        $result = [];
        $count = min($n, count($hm));
        for ($i = 0; $i < $count; $i++) {
            $objNum = (int) $hm[$i][1];
            $start = $first + (int) $hm[$i][2];
            $end = ($i + 1 < $count)
                ? $first + (int) $hm[$i + 1][2]
                : strlen($decoded);
            $body = trim(substr($decoded, $start, $end - $start));
            if ($body !== '') {
                $result[$objNum] = $body;
            }
        }

        return $result;
    }

    /**
     * Extract the raw (still-encoded) bytes of an object's stream.
     *
     * @param  array<int, array{inner: string, dict: string}>  $objects
     */
    protected function getStreamData(string $inner, array $objects): ?string
    {
        if (! preg_match('/stream(\r\n|\r|\n)/', $inner, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $m[0][1] + strlen($m[0][0]);

        $length = $this->dictGetInt($this->extractLeadingDict($inner), 'Length');
        if ($length === null) {
            // Length is indirect — resolve it from the object map.
            $ref = $this->dictGetRefNumber($this->extractLeadingDict($inner), 'Length');
            if ($ref !== null && isset($objects[$ref])) {
                $length = (int) trim($objects[$ref]['inner']);
            }
        }

        if ($length !== null && $length >= 0 && $start + $length <= strlen($inner)) {
            $candidate = substr($inner, $start, $length);
            // Sanity check that endstream follows (allowing for EOL padding).
            $after = substr($inner, $start + $length, 20);
            if (strpos($after, 'endstream') !== false) {
                return $candidate;
            }
        }

        // Fallback: slice up to the endstream marker.
        $end = strpos($inner, 'endstream', $start);
        if ($end === false) {
            return null;
        }
        $data = substr($inner, $start, $end - $start);

        // Trim a single trailing EOL that belongs to the "endstream" delimiter.
        if (substr($data, -2) === "\r\n") {
            $data = substr($data, 0, -2);
        } elseif (substr($data, -1) === "\n" || substr($data, -1) === "\r") {
            $data = substr($data, 0, -1);
        }

        return $data;
    }

    /**
     * Apply the stream's filters (FlateDecode + optional predictor) to obtain
     * the decoded payload. Only the filters needed for object streams are
     * supported; anything else yields null.
     *
     * @param  array<int, array{inner: string, dict: string}>  $objects
     */
    protected function decodeStream(string $data, string $dict, array $objects): ?string
    {
        $filter = $this->dictGetName($dict, 'Filter');
        if ($filter === null) {
            // No filter — already plain.
            return $data;
        }

        if ($filter !== 'FlateDecode' && $filter !== 'Fl') {
            return null; // Unsupported filter for our purposes.
        }

        $decoded = @gzuncompress($data);
        if ($decoded === false) {
            $decoded = @gzinflate($data);
        }
        if ($decoded === false) {
            return null;
        }

        // Apply a PNG/TIFF predictor if declared in DecodeParms.
        $predictor = $this->decodeParmsInt($dict, 'Predictor');
        if ($predictor !== null && $predictor >= 2) {
            $columns = $this->decodeParmsInt($dict, 'Columns') ?? 1;
            $colors = $this->decodeParmsInt($dict, 'Colors') ?? 1;
            $bpc = $this->decodeParmsInt($dict, 'BitsPerComponent') ?? 8;
            $decoded = $this->reversePredictor($decoded, $predictor, $columns, $colors, $bpc);
            if ($decoded === null) {
                return null;
            }
        }

        return $decoded;
    }

    /**
     * Reverse PNG (>=10) or TIFF (2) predictors applied before compression.
     */
    protected function reversePredictor(string $data, int $predictor, int $columns, int $colors, int $bpc): ?string
    {
        $bpp = max(1, (int) ceil($colors * $bpc / 8));
        $rowLen = (int) ceil($colors * $bpc * $columns / 8);
        if ($rowLen <= 0) {
            return null;
        }

        if ($predictor === 2) {
            // TIFF predictor 2 (horizontal differencing), 8-bit components only.
            if ($bpc !== 8) {
                return null;
            }
            $out = '';
            $rows = str_split($data, $rowLen);
            foreach ($rows as $row) {
                $bytes = array_values(unpack('C*', $row));
                for ($i = $bpp; $i < count($bytes); $i++) {
                    $bytes[$i] = ($bytes[$i] + $bytes[$i - $bpp]) & 0xFF;
                }
                $out .= pack('C*', ...$bytes);
            }

            return $out;
        }

        // PNG predictors: each row is prefixed with a filter-type byte.
        $stride = $rowLen + 1;
        $out = '';
        $prev = array_fill(0, $rowLen, 0);
        $offset = 0;
        $len = strlen($data);
        while ($offset + $stride <= $len) {
            $ft = ord($data[$offset]);
            $row = array_values(unpack('C*', substr($data, $offset + 1, $rowLen)));
            $offset += $stride;

            $cur = [];
            for ($i = 0; $i < $rowLen; $i++) {
                $a = $i >= $bpp ? $cur[$i - $bpp] : 0;      // left
                $b = $prev[$i];                              // up
                $c = $i >= $bpp ? $prev[$i - $bpp] : 0;      // upper-left
                $x = $row[$i];

                switch ($ft) {
                    case 0: $val = $x;
                        break;                 // None
                    case 1: $val = $x + $a;
                        break;            // Sub
                    case 2: $val = $x + $b;
                        break;            // Up
                    case 3: $val = $x + (int) (($a + $b) / 2);
                        break; // Average
                    case 4: $val = $x + $this->paeth($a, $b, $c);
                        break; // Paeth
                    default: return null;
                }
                $cur[$i] = $val & 0xFF;
            }
            $out .= pack('C*', ...$cur);
            $prev = $cur;
        }

        return $out;
    }

    protected function paeth(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);
        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        }

        return $pb <= $pc ? $b : $c;
    }

    /**
     * Emit all objects with a classic cross-reference table and trailer.
     *
     * @param  array<int, string>  $flat  objNum => object body
     * @param  array{root: string, info: ?string}  $trailer
     */
    protected function assemble(array $flat, array $trailer): string
    {
        ksort($flat);
        $maxNum = array_key_last($flat);
        $size = $maxNum + 1;

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        for ($num = 1; $num <= $maxNum; $num++) {
            if (! isset($flat[$num])) {
                continue;
            }
            $offsets[$num] = strlen($out);
            $out .= $num." 0 obj\n".$flat[$num]."\nendobj\n";
        }

        $xrefOffset = strlen($out);
        $lines = "xref\n0 ".$size."\n";
        $lines .= "0000000000 65535 f \n";
        for ($num = 1; $num <= $maxNum; $num++) {
            if (isset($offsets[$num])) {
                $lines .= sprintf("%010d 00000 n \n", $offsets[$num]);
            } else {
                $lines .= "0000000000 65535 f \n";
            }
        }

        $out .= $lines;
        $out .= "trailer\n<< /Size ".$size.' /Root '.$trailer['root'];
        if (! empty($trailer['info'])) {
            $out .= ' /Info '.$trailer['info'];
        }
        $out .= " >>\nstartxref\n".$xrefOffset."\n%%EOF\n";

        return $out;
    }

    // ---- Small dictionary helpers ---------------------------------------

    /**
     * Return the leading `<< ... >>` dictionary of an object body via balanced
     * matching of the << and >> delimiters.
     */
    protected function extractLeadingDict(string $inner): string
    {
        $start = strpos($inner, '<<');
        if ($start === false) {
            return '';
        }

        $depth = 0;
        $i = $start;
        $len = strlen($inner);
        while ($i < $len - 1) {
            $two = substr($inner, $i, 2);
            if ($two === '<<') {
                $depth++;
                $i += 2;

                continue;
            }
            if ($two === '>>') {
                $depth--;
                $i += 2;
                if ($depth === 0) {
                    return substr($inner, $start, $i - $start);
                }

                continue;
            }
            $i++;
        }

        return substr($inner, $start);
    }

    /**
     * Read a name value (without the leading slash), e.g. dictGetName($d, 'Type').
     */
    protected function dictGetName(string $dict, string $key): ?string
    {
        if (preg_match('/\/'.preg_quote($key, '/').'\s*\/([A-Za-z0-9.\-+#]+)/', $dict, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function dictGetInt(string $dict, string $key): ?int
    {
        if (preg_match('/\/'.preg_quote($key, '/').'\s+(\d+)(?!\s+\d+\s+R)/', $dict, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Return an indirect reference value verbatim, e.g. "1 0 R".
     */
    protected function dictGetRef(string $dict, string $key): ?string
    {
        if (preg_match('/\/'.preg_quote($key, '/').'\s+(\d+\s+\d+\s+R)/', $dict, $m)) {
            return preg_replace('/\s+/', ' ', $m[1]);
        }

        return null;
    }

    protected function dictGetRefNumber(string $dict, string $key): ?int
    {
        if (preg_match('/\/'.preg_quote($key, '/').'\s+(\d+)\s+\d+\s+R/', $dict, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Read an integer from the /DecodeParms dictionary (handles a single dict;
     * arrays of parms are uncommon for object streams).
     */
    protected function decodeParmsInt(string $dict, string $key): ?int
    {
        if (! preg_match('/\/DecodeParms\s*(<<.*?>>)/s', $dict, $m)) {
            return null;
        }

        return $this->dictGetInt($m[1], $key);
    }
}
