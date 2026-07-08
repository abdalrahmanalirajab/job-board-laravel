<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isEmployer();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'responsibilities' => 'sometimes|required|string',
            'benefits' => 'sometimes|nullable|string',
            'skills_required' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'location' => 'sometimes|required|string|max:255',
            'work_type' => 'sometimes|required|string|in:remote,onsite,hybrid',
            'experience_level' => 'sometimes|required|string|in:junior,mid,senior,any',
            'salary_min' => 'sometimes|nullable|integer|min:0',
            'salary_max' => 'sometimes|nullable|integer|min:0',
            'deadline' => 'sometimes|nullable|date|after:today',
            'technologies' => 'sometimes|nullable|array',
            'technologies.*' => 'string|max:100',
            'logo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
