<?php

namespace App\Domain\Events;

class ApplicationSubmitted
{
    public function __construct(
        public readonly int $applicationId,
        public readonly int $jobId,
        public readonly int $candidateId,
        public readonly int $employerId,
    ) {}
}
