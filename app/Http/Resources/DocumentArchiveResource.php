<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentArchiveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_name' => $this->document_name,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'category' => $this->category,
            'description' => $this->description,
            'tags' => $this->tags ? explode(',', $this->tags) : [],
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->file_size ? $this->formatFileSize($this->file_size) : null,
            'status' => $this->status,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'is_expired' => $this->expiry_date ? $this->expiry_date->isPast() : false,
            'days_until_expiry' => $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null,
            'version' => $this->version,
            'is_archived' => $this->is_archived,
            'candidate' => [
                'id' => $this->candidate?->id,
                'name' => $this->candidate?->name,
                'btevta_id' => $this->candidate?->btevta_id,
            ],
            'campus' => [
                'id' => $this->campus?->id,
                'name' => $this->campus?->name,
            ],
            'uploaded_by' => [
                'id' => $this->uploadedBy?->id,
                'name' => $this->uploadedBy?->name,
            ],
            'versions_count' => $this->when(isset($this->versions), fn() => $this->versions->count()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format file size in human-readable format
     */
    protected function formatFileSize($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
