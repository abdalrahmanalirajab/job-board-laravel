<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;



class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be authenticated to update a profile
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'avatar' => ['sometimes', 'image', 'max:2048'],
        ];

        // if employer
        if ($user && $user->isEmployer()) {
            $rules['company_name'] = ['sometimes', 'string'];
            $rules['website'] = ['sometimes', 'url'];
            $rules['description'] = ['sometimes', 'nullable', 'string'];
            $rules['logo'] = ['sometimes', 'nullable', 'image', 'max:2048'];
        }

        // if candidate
        if ($user && $user->isCandidate()) {
            $rules['linkedin_url'] = ['sometimes', 'url'];
            $rules['bio'] = ['sometimes', 'string'];
            $rules['skills'] = ['sometimes', 'array'];
            $rules['skills.*'] = ['string', 'max:255'];
            $rules['resume'] = ['sometimes', 'file', 'mimes:pdf', 'max:5120'];
        }

        return $rules;
    }
}
