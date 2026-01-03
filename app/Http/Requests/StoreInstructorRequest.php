<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstructorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * AUDIT FIX: Changed from simple auth()->check() to proper role-based authorization.
     * Previously any authenticated user could create instructors.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Only admins and campus admins can create instructors
        return $user->isSuperAdmin() ||
               $user->isProjectDirector() ||
               $user->isCampusAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'cnic' => 'required|string|max:15|unique:instructors,cnic',
            'email' => 'required|email|unique:instructors,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'specialization' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'nullable|exists:trades,id',
            'employment_type' => 'required|in:permanent,contract,visiting',
            'joining_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave,terminated',
            'photo_path' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cnic' => 'CNIC',
            'experience_years' => 'years of experience',
            'campus_id' => 'campus',
            'trade_id' => 'trade',
        ];
    }
}
