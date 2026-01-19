<?php

namespace App\Http\Requests;

use App\Enums\EvidenceType;
use Illuminate\Foundation\Http\FormRequest;

class StoreSuccessStoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\SuccessStory::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $evidenceTypes = implode(',', array_keys(EvidenceType::toArray()));

        return [
            'candidate_id' => 'required|exists:candidates,id',
            'departure_id' => 'nullable|exists:departures,id',
            'written_note' => 'required|string|max:5000',
            'evidence_type' => 'required|string|in:' . $evidenceTypes,
            'evidence' => 'nullable|file|max:51200', // 50MB max for video
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('evidence') && $this->has('evidence_type')) {
                $evidenceType = EvidenceType::tryFrom($this->evidence_type);
                if ($evidenceType) {
                    $allowedMimes = $evidenceType->allowedMimes();
                    $file = $this->file('evidence');
                    $mime = $file->getMimeType();

                    if (!in_array($mime, $allowedMimes)) {
                        $validator->errors()->add(
                            'evidence',
                            'Invalid file type for selected evidence type. Allowed: ' . implode(', ', $allowedMimes)
                        );
                    }
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'candidate_id' => 'candidate',
            'departure_id' => 'departure record',
            'written_note' => 'success story',
            'evidence_type' => 'evidence type',
            'is_featured' => 'featured status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'candidate_id.required' => 'Please select a candidate.',
            'candidate_id.exists' => 'The selected candidate is invalid.',
            'written_note.required' => 'Please enter the success story.',
            'written_note.max' => 'Success story cannot exceed 5000 characters.',
            'evidence_type.required' => 'Please select the evidence type.',
            'evidence_type.in' => 'The selected evidence type is invalid.',
            'evidence.max' => 'Evidence file size cannot exceed 50MB.',
        ];
    }
}
