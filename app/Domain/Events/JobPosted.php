<?php

namespace App\Domain\Events;

class JobPosted
{
    public function __construct(
        public readonly int $jobId,
        public readonly int $employerId,
        public readonly string $title,
    ) {}
}
