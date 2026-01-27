<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreDepartureDocumentResource extends JsonResource
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
            'candidate_id' => $this->candidate_id,
            'document_checklist_id' => $this->document_checklist_id,
            'document_checklist' => [
                'id' => $this->documentChecklist->id,
                'name' => $this->documentChecklist->name,
                'code' => $this->documentChecklist->code,
                'category' => $this->documentChecklist->category,
                'is_mandatory' => $this->documentChecklist->is_mandatory,
            ],
            'file_path' => $this->file_path,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->formatFileSize($this->file_size),
            'notes' => $this->notes,
            'uploaded_at' => $this->uploaded_at?->toDateTimeString(),
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', function () {
                return [
                    'id' => $this->uploader->id,
                    'name' => $this->uploader->name,
                ];
            }),
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'verified_by' => $this->verified_by,
            'verifier' => $this->whenLoaded('verifier', function () {
                return $this->verifier ? [
                    'id' => $this->verifier->id,
                    'name' => $this->verifier->name,
                ] : null;
            }),
            'verification_notes' => $this->verification_notes,
            'is_verified' => $this->isVerified(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Format file size in human-readable format
     */
    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
