<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CommentCreatedNotification extends Notification
{
    public function __construct(
        public readonly int $commentId,
        public readonly int $jobId,
        public readonly string $jobTitle,
        public readonly string $commenterName,
        public readonly string $body,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'comment_created',
            'comment_id'     => $this->commentId,
            'job_id'         => $this->jobId,
            'job_title'      => $this->jobTitle,
            'commenter_name' => $this->commenterName,
            'comment_body'   => $this->body,
            'message'        => "{$this->commenterName} commented on {$this->jobTitle}",
            'link'           => "/jobs/{$this->jobId}",
            'icon'           => 'chat-bubble',
        ];
    }
}
