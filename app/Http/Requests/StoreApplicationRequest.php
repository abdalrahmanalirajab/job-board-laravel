<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'resume' => 'nullable|file|mimes:pdf|max:5120',
      'resume_url' => 'nullable|string|max:2048',
      'resume_name' => 'nullable|string|max:255',
      'contact_email' => 'nullable|email',
      'contact_phone' => 'nullable|string|max:20',
      'email' => 'nullable|email',
      'phone' => 'nullable|string|max:20',
      'linkedin' => 'nullable|string|max:255',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $hasResume = $this->hasFile('resume') || $this->filled('resume_url');
      $hasEmail = $this->filled('contact_email') || $this->filled('email');
      if (!$hasResume && !$hasEmail) {
        $validator->errors()->add('resume', 'At least one of resume or contact email must be provided.');
        $validator->errors()->add('contact_email', 'At least one of resume or contact email must be provided.');
      }
    });
  }

  public function messages(): array
  {
    return [
      'resume.max' => 'The resume file must not be larger than 5MB.',
      'resume.mimes' => 'The resume must be a PDF file.',
    ];
  }
}
