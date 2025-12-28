<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\SecureFileController;

/**
 * FileStorageService
 *
 * Centralized service for handling all file uploads in the application.
 * SECURITY: This service ensures sensitive documents are stored on the
 * private disk and only accessible via authenticated routes.
 */
class FileStorageService
{
    /**
     * Document categories and their sensitivity levels.
     *
     * 'private' - Requires authentication to access (CNIC, passport, medical)
     * 'public' - Publicly accessible (profile photos)
     */
    const DOCUMENT_CATEGORIES = [
        // Candidate Documents - PRIVATE
        'cnic' => ['disk' => 'private', 'path' => 'candidates/{id}/cnic'],
        'passport' => ['disk' => 'private', 'path' => 'candidates/{id}/passport'],
        'photo' => ['disk' => 'photos', 'path' => '{id}'],
        'education' => ['disk' => 'private', 'path' => 'candidates/{id}/education'],
        'experience' => ['disk' => 'private', 'path' => 'candidates/{id}/experience'],
        'medical' => ['disk' => 'private', 'path' => 'candidates/{id}/medical'],

        // Screening Documents - PRIVATE
        'screening_evidence' => ['disk' => 'private', 'path' => 'screening/{id}/evidence'],

        // Registration Documents - PRIVATE
        'registration' => ['disk' => 'private', 'path' => 'candidates/{id}/registration'],
        'undertaking' => ['disk' => 'private', 'path' => 'candidates/{id}/undertaking'],

        // Training Documents - PRIVATE
        'certificate' => ['disk' => 'private', 'path' => 'candidates/{id}/certificates'],
        'assessment' => ['disk' => 'private', 'path' => 'training/{id}/assessments'],

        // Visa Documents - PRIVATE
        'visa_document' => ['disk' => 'private', 'path' => 'visa/{id}/documents'],
        'trade_test' => ['disk' => 'private', 'path' => 'visa/{id}/trade_test'],
        'takamol' => ['disk' => 'private', 'path' => 'visa/{id}/takamol'],
        'gamca' => ['disk' => 'private', 'path' => 'visa/{id}/gamca'],
        'ticket' => ['disk' => 'private', 'path' => 'visa/{id}/ticket'],

        // Departure Documents - PRIVATE
        'departure' => ['disk' => 'private', 'path' => 'departures/{id}/documents'],
        'iqama' => ['disk' => 'private', 'path' => 'departures/{id}/iqama'],
        'post_arrival' => ['disk' => 'private', 'path' => 'departures/{id}/post_arrival'],

        // Remittance Documents - PRIVATE
        'remittance_receipt' => ['disk' => 'private', 'path' => 'remittances/{id}/receipts'],
        'remittance_proof' => ['disk' => 'private', 'path' => 'remittances/{id}/proof'],

        // Complaint Documents - PRIVATE
        'complaint_evidence' => ['disk' => 'private', 'path' => 'complaints/{id}/evidence'],

        // Correspondence - PRIVATE
        'correspondence' => ['disk' => 'private', 'path' => 'correspondence/{id}'],

        // Document Archive - PRIVATE
        'archive' => ['disk' => 'private', 'path' => 'documents/{id}'],
    ];

    /**
     * Store a file securely.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $category The document category (from DOCUMENT_CATEGORIES)
     * @param int|string $resourceId The ID of the related resource (candidate, complaint, etc.)
     * @param string|null $customFilename Optional custom filename
     * @return array ['disk' => string, 'path' => string, 'url' => string, 'original_name' => string]
     */
    public function store(UploadedFile $file, string $category, $resourceId, ?string $customFilename = null): array
    {
        $config = self::DOCUMENT_CATEGORIES[$category] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Unknown document category: {$category}");
        }

        $disk = $config['disk'];
        $basePath = str_replace('{id}', $resourceId, $config['path']);

        // Generate unique filename if not provided
        if ($customFilename) {
            $filename = $customFilename;
        } else {
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
        }

        $fullPath = $basePath . '/' . $filename;

        // Store the file with error handling
        try {
            $stored = Storage::disk($disk)->putFileAs($basePath, $file, $filename);

            if (!$stored) {
                throw new \RuntimeException("Failed to store file to disk: {$disk}");
            }
        } catch (\Exception $e) {
            Log::error('File storage failed', [
                'disk' => $disk,
                'path' => $fullPath,
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("File storage failed: {$e->getMessage()}", 0, $e);
        }

        // Generate URL based on disk type
        $url = $this->generateUrl($disk, $fullPath);

        return [
            'disk' => $disk,
            'path' => $fullPath,
            'url' => $url,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Store a file from raw content.
     *
     * @param string $content The file content
     * @param string $category The document category
     * @param int|string $resourceId The resource ID
     * @param string $filename The filename with extension
     * @return array
     */
    public function storeContent(string $content, string $category, $resourceId, string $filename): array
    {
        $config = self::DOCUMENT_CATEGORIES[$category] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Unknown document category: {$category}");
        }

        $disk = $config['disk'];
        $basePath = str_replace('{id}', $resourceId, $config['path']);
        $fullPath = $basePath . '/' . $filename;

        // Store content with error handling
        try {
            $stored = Storage::disk($disk)->put($fullPath, $content);

            if (!$stored) {
                throw new \RuntimeException("Failed to store content to disk: {$disk}");
            }
        } catch (\Exception $e) {
            Log::error('Content storage failed', [
                'disk' => $disk,
                'path' => $fullPath,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Content storage failed: {$e->getMessage()}", 0, $e);
        }

        return [
            'disk' => $disk,
            'path' => $fullPath,
            'url' => $this->generateUrl($disk, $fullPath),
            'original_name' => $filename,
            'size' => strlen($content),
        ];
    }

    /**
     * Delete a file.
     *
     * @param string $disk The storage disk
     * @param string $path The file path
     * @return bool
     */
    public function delete(string $disk, string $path): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Check if a file exists.
     *
     * @param string $disk The storage disk
     * @param string $path The file path
     * @return bool
     */
    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file contents.
     *
     * @param string $disk The storage disk
     * @param string $path The file path
     * @return string|null
     */
    public function get(string $disk, string $path): ?string
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->get($path);
        }

        return null;
    }

    /**
     * Generate the appropriate URL for a file.
     *
     * @param string $disk The storage disk
     * @param string $path The file path
     * @return string
     */
    public function generateUrl(string $disk, string $path): string
    {
        if ($disk === 'private') {
            // Use secure route for private files
            return SecureFileController::secureUrl($path);
        }

        // Public files can use direct URL
        return Storage::disk($disk)->url($path);
    }

    /**
     * Generate a view URL (for inline display).
     *
     * @param string $disk The storage disk
     * @param string $path The file path
     * @return string
     */
    public function generateViewUrl(string $disk, string $path): string
    {
        if ($disk === 'private') {
            return SecureFileController::secureViewUrl($path);
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Move a file from one location to another.
     *
     * @param string $sourceDisk
     * @param string $sourcePath
     * @param string $destDisk
     * @param string $destPath
     * @return bool
     */
    public function move(string $sourceDisk, string $sourcePath, string $destDisk, string $destPath): bool
    {
        if (!Storage::disk($sourceDisk)->exists($sourcePath)) {
            Log::warning('Move failed: source file not found', [
                'source_disk' => $sourceDisk,
                'source_path' => $sourcePath,
            ]);
            return false;
        }

        try {
            $content = Storage::disk($sourceDisk)->get($sourcePath);
            $stored = Storage::disk($destDisk)->put($destPath, $content);

            if (!$stored) {
                throw new \RuntimeException("Failed to write to destination");
            }

            Storage::disk($sourceDisk)->delete($sourcePath);
            return true;
        } catch (\Exception $e) {
            Log::error('File move failed', [
                'source_disk' => $sourceDisk,
                'source_path' => $sourcePath,
                'dest_disk' => $destDisk,
                'dest_path' => $destPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Copy a file.
     *
     * @param string $sourceDisk
     * @param string $sourcePath
     * @param string $destDisk
     * @param string $destPath
     * @return bool
     */
    public function copy(string $sourceDisk, string $sourcePath, string $destDisk, string $destPath): bool
    {
        if (!Storage::disk($sourceDisk)->exists($sourcePath)) {
            Log::warning('Copy failed: source file not found', [
                'source_disk' => $sourceDisk,
                'source_path' => $sourcePath,
            ]);
            return false;
        }

        try {
            $content = Storage::disk($sourceDisk)->get($sourcePath);
            $stored = Storage::disk($destDisk)->put($destPath, $content);

            if (!$stored) {
                throw new \RuntimeException("Failed to write to destination");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('File copy failed', [
                'source_disk' => $sourceDisk,
                'source_path' => $sourcePath,
                'dest_disk' => $destDisk,
                'dest_path' => $destPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get file size.
     *
     * @param string $disk
     * @param string $path
     * @return int|null
     */
    public function size(string $disk, string $path): ?int
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->size($path);
        }

        return null;
    }

    /**
     * Get file MIME type.
     *
     * @param string $disk
     * @param string $path
     * @return string|null
     */
    public function mimeType(string $disk, string $path): ?string
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->mimeType($path);
        }

        return null;
    }

    /**
     * List files in a directory.
     *
     * @param string $disk
     * @param string $directory
     * @return array
     */
    public function listFiles(string $disk, string $directory): array
    {
        return Storage::disk($disk)->files($directory);
    }

    /**
     * Validate file upload.
     *
     * @param UploadedFile $file
     * @param array $allowedMimes
     * @param int $maxSizeKb
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validate(UploadedFile $file, array $allowedMimes = [], int $maxSizeKb = 5120): array
    {
        // Check file size (default 5MB)
        if ($file->getSize() > $maxSizeKb * 1024) {
            return [
                'valid' => false,
                'error' => "File size exceeds maximum allowed ({$maxSizeKb}KB)",
            ];
        }

        // Check MIME type if specified
        if (!empty($allowedMimes) && !in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedMimes),
            ];
        }

        // SECURITY: Comprehensive check for dangerous file extensions
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = [
            // PHP variants
            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar',
            // Windows executables and scripts
            'exe', 'com', 'msi', 'bat', 'cmd', 'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psm1',
            // Unix/Linux scripts
            'sh', 'bash', 'csh', 'ksh', 'zsh',
            // Other server-side scripts
            'jsp', 'jspx', 'asp', 'aspx', 'cgi', 'pl', 'py', 'rb',
            // JavaScript
            'js', 'mjs',
            // HTML/SVG (can contain malicious scripts)
            'html', 'htm', 'xhtml', 'svg',
            // Server config files
            'htaccess', 'htpasswd',
            // Java
            'jar', 'war', 'class',
            // Other potentially dangerous
            'dll', 'so', 'dylib', 'scr', 'reg', 'inf', 'hta',
        ];

        if (in_array($extension, $dangerousExtensions)) {
            Log::warning('Blocked dangerous file upload attempt', [
                'extension' => $extension,
                'original_name' => $file->getClientOriginalName(),
                'user_id' => auth()->id(),
            ]);
            return [
                'valid' => false,
                'error' => 'File type not allowed for security reasons',
            ];
        }

        // SECURITY: Check for double extensions (e.g., file.php.jpg)
        $filename = $file->getClientOriginalName();
        foreach ($dangerousExtensions as $dangerousExt) {
            if (preg_match('/\.' . preg_quote($dangerousExt, '/') . '\./i', $filename)) {
                Log::warning('Blocked double extension upload attempt', [
                    'filename' => $filename,
                    'user_id' => auth()->id(),
                ]);
                return [
                    'valid' => false,
                    'error' => 'File name contains suspicious patterns',
                ];
            }
        }

        // SECURITY: Validate file content using magic bytes
        $magicBytesResult = $this->validateMagicBytes($file);
        if (!$magicBytesResult['valid']) {
            return $magicBytesResult;
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate file content using magic bytes (file signatures).
     * This prevents file type spoofing where a malicious file has a fake extension.
     *
     * @param UploadedFile $file
     * @return array ['valid' => bool, 'error' => string|null]
     */
    protected function validateMagicBytes(UploadedFile $file): array
    {
        // Read first 12 bytes for magic number detection
        $handle = fopen($file->getRealPath(), 'rb');
        if (!$handle) {
            return ['valid' => false, 'error' => 'Unable to read file for validation'];
        }
        $bytes = fread($handle, 12);
        fclose($handle);

        if ($bytes === false || strlen($bytes) < 4) {
            return ['valid' => false, 'error' => 'File too small for validation'];
        }

        // Common magic bytes signatures
        $signatures = [
            // Images
            'jpg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89PNG\r\n\x1a\n"],
            'gif' => ["GIF87a", "GIF89a"],
            'webp' => ["RIFF"],
            'bmp' => ["BM"],
            'ico' => ["\x00\x00\x01\x00"],
            // Documents
            'pdf' => ["%PDF"],
            'zip' => ["PK\x03\x04", "PK\x05\x06"],
            'rar' => ["Rar!\x1a\x07"],
            '7z' => ["7z\xBC\xAF"],
            'docx' => ["PK\x03\x04"], // Office Open XML
            'xlsx' => ["PK\x03\x04"],
            'pptx' => ["PK\x03\x04"],
            // Legacy Office
            'doc' => ["\xD0\xCF\x11\xE0"],
            'xls' => ["\xD0\xCF\x11\xE0"],
            'ppt' => ["\xD0\xCF\x11\xE0"],
        ];

        $extension = strtolower($file->getClientOriginalExtension());

        // If we have a signature for this extension, validate it
        if (isset($signatures[$extension])) {
            $validSignature = false;
            foreach ($signatures[$extension] as $sig) {
                if (substr($bytes, 0, strlen($sig)) === $sig) {
                    $validSignature = true;
                    break;
                }
            }

            if (!$validSignature) {
                Log::warning('Magic bytes mismatch detected', [
                    'extension' => $extension,
                    'original_name' => $file->getClientOriginalName(),
                    'actual_bytes' => bin2hex(substr($bytes, 0, 8)),
                    'user_id' => auth()->id(),
                ]);
                return [
                    'valid' => false,
                    'error' => 'File content does not match its extension',
                ];
            }
        }

        // Check for PHP code in the file content (common attack vector)
        $content = file_get_contents($file->getRealPath());
        if ($content !== false) {
            $phpPatterns = [
                '<?php',
                '<?=',
                '<script language="php">',
                '<script language=php>',
            ];
            foreach ($phpPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    Log::warning('PHP code detected in uploaded file', [
                        'original_name' => $file->getClientOriginalName(),
                        'user_id' => auth()->id(),
                    ]);
                    return [
                        'valid' => false,
                        'error' => 'File contains potentially malicious content',
                    ];
                }
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Migrate existing files from public to private disk.
     *
     * CAUTION: Run this during maintenance window.
     *
     * @param string $category
     * @return array ['migrated' => int, 'errors' => array]
     */
    public function migrateToPrivate(string $category): array
    {
        $config = self::DOCUMENT_CATEGORIES[$category] ?? null;

        if (!$config || $config['disk'] !== 'private') {
            return ['migrated' => 0, 'errors' => ['Category not configured for private storage']];
        }

        // This would need to be implemented based on the specific migration needs
        // For now, return a placeholder
        return ['migrated' => 0, 'errors' => ['Migration not implemented yet']];
    }
}
