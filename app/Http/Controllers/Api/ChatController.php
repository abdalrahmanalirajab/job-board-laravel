<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
use App\Events\NewMessage;
use App\Events\TypingEvent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::forUser($userId)
            ->with(['job', 'candidate', 'employer', 'lastMessage.sender'])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) use ($userId) {
                $other = $conv->otherParticipant($userId);
                return [
                    'id' => $conv->id,
                    'job' => $conv->job ? [
                        'id' => $conv->job->id,
                        'title' => $conv->job->title,
                    ] : null,
                    'other_user' => $other ? [
                        'id' => $other->id,
                        'name' => $other->name,
                        'email' => $other->email,
                    ] : null,
                    'last_message' => $conv->lastMessage ? [
                        'body' => $conv->lastMessage->body,
                        'sender_name' => $conv->lastMessage->sender->name,
                        'created_at' => $conv->lastMessage->created_at->toISOString(),
                    ] : null,
                    'unread_count' => $conv->unreadCount($userId),
                    'last_message_at' => $conv->last_message_at?->toISOString(),
                    'created_at' => $conv->created_at->toISOString(),
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_listing_id' => 'required|exists:job_listings,id',
        ]);

        $userId = $request->user()->id;
        $job = \App\Models\JobListing::findOrFail($validated['job_listing_id']);

        if (!$job->employer) {
            return response()->json(['message' => 'Employer not found for this job.'], 404);
        }

        $employerUserId = $job->employer->id;

        if ($userId === $employerUserId) {
            return response()->json(['message' => 'You cannot start a conversation with yourself.'], 422);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'job_listing_id' => $validated['job_listing_id'],
                'candidate_user_id' => $userId,
            ],
            [
                'employer_user_id' => $employerUserId,
                'last_message_at' => now(),
            ]
        );

        $conversation->load(['job', 'candidate', 'employer']);

        $other = $conversation->otherParticipant($userId);

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'job' => $conversation->job ? [
                    'id' => $conversation->job->id,
                    'title' => $conversation->job->title,
                ] : null,
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => $other->name,
                    'email' => $other->email,
                ] : null,
                'created_at' => $conversation->created_at->toISOString(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $conversation = Conversation::findOrFail($id);

        if ($conversation->candidate_user_id !== $userId && $conversation->employer_user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        $other = $conversation->otherParticipant($userId);

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'job' => $conversation->job ? [
                    'id' => $conversation->job->id,
                    'title' => $conversation->job->title,
                ] : null,
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => $other->name,
                    'email' => $other->email,
                ] : null,
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ],
        ]);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $userId = $request->user()->id;

        $conversation = Conversation::findOrFail($id);

        if ($conversation->candidate_user_id !== $userId && $conversation->employer_user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $message = Message::create([
            'conversation_id' => $id,
            'sender_user_id' => $userId,
            'body' => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $message->load('sender');

        broadcast(new NewMessage($message));

        return response()->json([
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_user_id' => $message->sender_user_id,
                'sender_name' => $message->sender->name,
                'body' => $message->body,
                'read_at' => null,
                'created_at' => $message->created_at->toISOString(),
            ],
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $conversation = Conversation::findOrFail($id);

        if ($conversation->candidate_user_id !== $userId && $conversation->employer_user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $now = now()->toISOString();

        $conversation->messages()
            ->where('sender_user_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);

        broadcast(new MessageRead($id, $userId, $now));

        return response()->json(['message' => 'Messages marked as read.']);
    }

    public function typing(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $userId = $request->user()->id;
        $user = $request->user();

        $conversation = Conversation::findOrFail($id);

        if ($conversation->candidate_user_id !== $userId && $conversation->employer_user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        broadcast(new TypingEvent($id, $userId, $user->name, $validated['is_typing']));

        return response()->json(['message' => 'Typing event broadcast.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = Message::whereHas('conversation', function ($q) use ($userId) {
            $q->forUser($userId);
        })
            ->where('sender_user_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }
}
