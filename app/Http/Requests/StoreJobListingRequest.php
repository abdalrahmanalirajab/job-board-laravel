<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobListingRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'responsibilities' => 'required|string',
            'skills_required' => 'required|string',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',
            'location' => 'required|string|max:255',
            'work_type' => 'required|string|in:remote,onsite,hybrid',
            'experience_level' => 'nullable|string|in:junior,mid,senior,any',
            'deadline' => 'nullable|date|after_or_equal:today',
            'logo' => 'nullable|image|max:2048',
            'technologies' => 'nullable|array',
            'technologies.*' => 'required|string|max:255',
        ];
    }
}
