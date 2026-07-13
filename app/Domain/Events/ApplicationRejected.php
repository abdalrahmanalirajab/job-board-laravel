<?php

namespace App\Domain\Events;

class ApplicationRejected
{
    public function __construct(
        public readonly int $applicationId,
        public readonly int $jobId,
        public readonly int $candidateId,
        public readonly int $employerId,
        public readonly ?string $reason = null,
    ) {}
}
