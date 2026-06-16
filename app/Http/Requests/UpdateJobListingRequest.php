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
            'category_id' => 'sometimes|required|exists:categories,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'responsibilities' => 'sometimes|required|string',
            'skills_required' => 'sometimes|required|string',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',
            'location' => 'sometimes|required|string|max:255',
            'work_type' => 'sometimes|required|string|in:remote,onsite,hybrid',
            'experience_level' => 'nullable|string|in:junior,mid,senior,any',
            'deadline' => 'nullable|date',
            'logo' => 'nullable|image|max:2048',
            'technologies' => 'nullable|array',
            'technologies.*' => 'required|string|max:255',
        ];
    }
}
