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
    public function index($jobId)
    {
        $job = JobListing::find($jobId);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.',
                'data' => null
            ], 404);
        }

        $comments = Comment::visible()
            ->where('job_listing_id', $job->id)
            ->with(['author' => function ($q) {
                $q->select('id', 'name', 'role', 'avatar');
            }])
            ->latest()
            ->paginate(15);

        return CommentResource::collection($comments)->additional([
            'success' => true,
            'message' => 'Comments retrieved successfully.'
        ]);
    }

    /**
     * Add a comment to a job
     */
    public function store(StoreCommentRequest $request, $jobId)
    {
        $job = JobListing::find($jobId);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job listing not found.',
                'data' => null
            ], 404);
        }

        if ($job->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot comment on a job listing that is not approved.',
                'data' => null
            ], 422);
        }

        $comment = Comment::create([
            'job_listing_id' => $job->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'is_visible' => true,
        ]);

        return (new CommentResource($comment->load('author')))
            ->additional([
                'success' => true,
                'message' => 'Comment added successfully.'
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete a comment (admin hides, owner deletes)
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
                'data' => null
            ], 404);
        }

        $user = auth()->user();

        if ($user->isAdmin()) {
            $comment->update(['is_visible' => false]);
            return response()->json([
                'success' => true,
                'message' => 'Comment hidden successfully.',
                'data' => null
            ]);
        }

        if ($comment->user_id === $user->id) {
            $comment->delete();
            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to delete this comment.',
            'data' => null
        ], 403);
    }
}