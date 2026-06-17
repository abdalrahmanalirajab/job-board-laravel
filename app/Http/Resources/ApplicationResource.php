<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'job_listing_id' => $this->job_listing_id,
      'candidate_id' => $this->candidate_id,
      'candidate_name' => $this->whenLoaded('candidate', fn() => $this->candidate->name),
      'candidate_email' => $this->whenLoaded('candidate', fn() => $this->candidate->email),
      'resume_url' => $this->resume_path ? url('storage/' . $this->resume_path) : null,
      'contact_email' => $this->contact_email,
      'contact_phone' => $this->contact_phone,
      'status' => $this->status,
      'applied_at' => $this->applied_at,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}