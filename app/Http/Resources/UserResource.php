<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $profile = null;
        if ($this->isEmployer() && ($this->relationLoaded('employerProfile') || $this->employerProfile)) {
            $profile = new EmployerProfileResource($this->employerProfile);
        } elseif ($this->isCandidate() && ($this->relationLoaded('candidateProfile') || $this->candidateProfile)) {
            $profile = new CandidateProfileResource($this->candidateProfile);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar ? asset(Storage::url($this->avatar)) : null,
            'created_at' => $this->created_at,
            'profile' => $profile,
        ];
    }
}
