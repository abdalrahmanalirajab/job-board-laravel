<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
       $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['employer', 'candidate'])],
        ];

        if ($this->input('role') === 'employer') {
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['website'] = ['nullable', 'url', 'max:255'];
            $rules['description'] = ['nullable', 'string'];
            $rules['logo'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        }

        if ($this->input('role') === 'candidate') {
            $rules['linkedin_url'] = ['nullable', 'url', 'max:255'];
            $rules['bio'] = ['nullable', 'string'];
            $rules['skills'] = ['nullable', 'string'];
            $rules['resume'] = ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096']; // Max 4MB files

        }
        return $rules;
    }
}
