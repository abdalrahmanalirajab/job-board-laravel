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
      'resume' => 'nullable|file|mimes:pdf|max:2048',
      'contact_email' => 'required_without:resume|email',
      'contact_phone' => 'nullable|string|max:20',
    ];
  }

  public function messages(): array
  {
    return [
      'resume.max' => 'The resume file must not be larger than 2MB.',
      'resume.mimes' => 'The resume must be a PDF file.',
    ];
  }
}