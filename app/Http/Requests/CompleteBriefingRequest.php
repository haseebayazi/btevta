<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteBriefingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acknowledgment_signed' => 'required|boolean|accepted',
            'notes' => 'nullable|string|max:2000',
            'briefing_document' => 'nullable|file|max:10240|mimes:pdf',
            'briefing_video' => 'nullable|file|max:102400|mimes:mp4,mov,avi',
            'acknowledgment_file' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ];
    }

    public function messages(): array
    {
        return [
            'acknowledgment_signed.accepted' => 'Candidate must acknowledge the pre-departure briefing.',
            'briefing_video.max' => 'Video file must be less than 100MB.',
            'briefing_video.mimes' => 'Video must be an MP4, MOV, or AVI file.',
        ];
    }
}
