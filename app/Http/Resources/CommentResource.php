<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $authorData = $this->whenLoaded('author', function () {
      $author = $this->author;
      if (!$author) return null;

      $avatarUrl = null;
      if ($author->avatar) {
        $avatarUrl = Str::startsWith($author->avatar, ['http://', 'https://'])
          ? $author->avatar
          : Storage::url($author->avatar);
      }

      return [
        'id' => $author->id,
        'name' => $author->name,
        'role' => $author->role,
        'avatar' => $avatarUrl,
      ];
    });

    $jobData = $this->whenLoaded('jobListing', function () {
      $job = $this->jobListing;
      if (!$job) return null;
      return [
        'id'    => $job->id,
        'title' => $job->title,
      ];
    });

    return [
      'id' => $this->id,
      'body' => $this->body,
      'is_visible' => $this->is_visible,
      'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
      'author' => $authorData,
      'job_post' => $jobData,
      'user' => $authorData,
    ];
  }
}