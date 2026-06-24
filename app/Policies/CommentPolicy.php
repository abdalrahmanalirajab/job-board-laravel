<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
  /**
   * Determine if a user can delete this comment.
   * Admin can delete any; users can delete their own.
   */
  public function delete(User $user, Comment $comment): bool
  {
    return $user->isAdmin() || $user->id === $comment->user_id;
  }
}