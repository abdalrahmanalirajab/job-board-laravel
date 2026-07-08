<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        try {
            $query = User::query();

            if ($request->filled('role')) {
                $query->where('role', $request->input('role'));
            }

            $users = $query->with(['employerProfile', 'candidateProfile'])
                ->latest()
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully.',
                'data'    => UserResource::collection($users)->resolve(),
                'meta'    => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'total'        => $users->total(),
                    'per_page'     => $users->perPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function comments(Request $request)
    {
        try {
            $query = Comment::with(['author', 'jobListing']);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('body', 'like', "%{$search}%")
                      ->orWhereHas('author', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $comments = $query->latest()->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Comments retrieved successfully.',
                'data'    => CommentResource::collection($comments)->resolve(),
                'meta'    => [
                    'current_page' => $comments->currentPage(),
                    'last_page'    => $comments->lastPage(),
                    'total'        => $comments->total(),
                    'per_page'     => $comments->perPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve comments: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function deleteComment($id)
    {
        try {
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found.',
                    'data'    => null,
                ], 404);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.',
                'data'    => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
