<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobListingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = (new JobListingResource($this))->toArray($request);

        return array_merge($base, [
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'skills_required' => $this->skills_required,
            'applications_count' => class_exists('App\\Models\\Application')
                ? (int) ($this->applications_count ?? ($this->relationLoaded('applications') ? $this->applications->count() : $this->applications()->count()))
                : 0,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ]);
    }
}
