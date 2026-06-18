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
        $user = $this->user() ;

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];

        // if employer
        if ($user->isEmployer()) {
            $rules['company_name'] = ['sometimes','required', 'string', 'max:255'];
            $rules['website'] = ['nullable', 'url', 'max:255'];
            $rules['description'] = ['nullable', 'string'];
            $rules['logo'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        }

        // if candidate
        if ($user->isCandidate()) {
            $rules['linkedin_url'] = ['nullable', 'url', 'max:255'];
            $rules['bio'] = ['nullable', 'string'];
            $rules['skills'] = ['nullable', 'string'];            $rules['resume'] = ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096']; // Max 4MB files
        }

        return $rules;
    }
}
