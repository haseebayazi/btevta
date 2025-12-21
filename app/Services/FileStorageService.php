<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        // Store the file
        Storage::disk($disk)->putFileAs($basePath, $file, $filename);

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

        Storage::disk($disk)->put($fullPath, $content);

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
            return false;
        }

        $content = Storage::disk($sourceDisk)->get($sourcePath);
        Storage::disk($destDisk)->put($destPath, $content);
        Storage::disk($sourceDisk)->delete($sourcePath);

        return true;
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
            return false;
        }

        $content = Storage::disk($sourceDisk)->get($sourcePath);
        return Storage::disk($destDisk)->put($destPath, $content);
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

        // Check for executable content in filename
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'sh', 'bat', 'cmd', 'js'];

        if (in_array($extension, $dangerousExtensions)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed for security reasons',
            ];
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
