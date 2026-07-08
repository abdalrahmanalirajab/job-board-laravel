<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CandidateProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resume_path' => $this->resume_path ? asset(Storage::url($this->resume_path)) : null,
            'linkedin_url' => $this->linkedin_url,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'skills' => $this->skills,
        ];
    }
}
