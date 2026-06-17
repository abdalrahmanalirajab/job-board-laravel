<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\JobListing;
use Illuminate\Http\Request;

class CommentController extends Controller
{
  /**
   * List visible comments for a job
   */
  public function index(JobListing $job)
  {
    $comments = Comment::visible()
      ->where('job_listing_id', $job->id)
      ->with('user')
      ->latest()
      ->paginate(20);

    return CommentResource::collection($comments);
  }

  /**
   * Add a comment to a job
   */
  public function store(StoreCommentRequest $request, JobListing $job)
  {
    $comment = Comment::create([
      'job_listing_id' => $job->id,
      'user_id' => $request->user()->id,
      'body' => $request->body,
      'is_visible' => true,
    ]);

    return new CommentResource($comment->load('user'));
  }

  /**
   * Delete a comment (admin or owner)
   */
  public function destroy(Request $request, Comment $comment)
  {
    $user = $request->user();

    // Admin can delete any comment, users can delete their own
    if ($user->role !== 'admin' && $comment->user_id !== $user->id) {
      return response()->json(['message' => 'You are not authorized to delete this comment.'], 403);
    }

    $comment->delete();

    return response()->json(['message' => 'Comment deleted successfully.']);
  }
}