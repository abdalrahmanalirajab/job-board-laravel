<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\CommentCreated;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentCreatedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendJobCommentNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(CommentCreated $event): void
    {
        try {
            $comment = Comment::with(['jobListing', 'author'])->find($event->commentId);

            if (!$comment) {
                return;
            }

            $job = $comment->jobListing;

            if (!$job) {
                return;
            }

            $employer = User::find($job->employer_id);

            if ($employer && $employer->id !== $event->userId) {
                $this->service->notify($employer, new CommentCreatedNotification(
                    $comment->id,
                    $job->id,
                    $job->title,
                    $comment->author->name,
                    $comment->body,
                ));
            }

            $candidateIds = $job->applications()
                ->where('status', '!=', 'rejected')
                ->pluck('candidate_id')
                ->unique()
                ->filter(fn ($id) => $id !== $event->userId);

            foreach ($candidateIds as $candidateId) {
                $candidate = User::find($candidateId);

                if ($candidate) {
                    $this->service->notify($candidate, new CommentCreatedNotification(
                        $comment->id,
                        $job->id,
                        $job->title,
                        $comment->author->name,
                        $comment->body,
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send job comment notification', [
                'comment_id' => $event->commentId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
