<?php

namespace App\Domain\Events;

class JobRejected
{
    public function __construct(
        public readonly int $jobId,
        public readonly int $employerId,
        public readonly string $title,
        public readonly ?string $reason = null,
    ) {}
}
