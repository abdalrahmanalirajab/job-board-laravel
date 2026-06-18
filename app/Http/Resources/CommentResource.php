<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'job_listing_id' => $this->job_listing_id,
      'user_id' => $this->user_id,
      'user_name' => $this->whenLoaded('user', fn() => $this->user->name),
      'body' => $this->body,
      'is_visible' => $this->is_visible,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}