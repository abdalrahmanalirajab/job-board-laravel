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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'responsibilities' => 'required|string',
            'benefits' => 'nullable|string',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:255',
            'skills_required' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'location' => 'required|string|max:255',
            'work_type' => 'required|string|in:remote,onsite,hybrid',
            'experience_level' => 'required|string|in:junior,mid,senior,any',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0',
            'deadline' => 'nullable|date|after:today',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:100',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
