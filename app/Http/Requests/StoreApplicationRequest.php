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
      'contact_email' => 'nullable|email',
      'contact_phone' => 'nullable|string|max:20',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      if (!$this->hasFile('resume') && !$this->filled('contact_email')) {
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