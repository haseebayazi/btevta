<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreDepartureDocumentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_departure_document_id',
        'page_number',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the parent document
     */
    public function document()
    {
        return $this->belongsTo(PreDepartureDocument::class, 'pre_departure_document_id');
    }
}
