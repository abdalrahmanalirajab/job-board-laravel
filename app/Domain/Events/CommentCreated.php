<?php

namespace App\Domain\Events;

class CommentCreated
{
    public function __construct(
        public readonly int $commentId,
        public readonly int $jobId,
        public readonly int $userId,
        public readonly string $body,
    ) {}
}
