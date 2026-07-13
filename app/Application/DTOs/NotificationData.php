<?php

namespace App\Application\DTOs;

class NotificationData
{
    public function __construct(
        public readonly string $type,
        public readonly string $message,
        public readonly ?int $applicationId = null,
        public readonly ?int $jobId = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $companyName = null,
        public readonly ?string $reason = null,
        public readonly ?float $amount = null,
        public readonly ?string $commentBody = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type'           => $this->type,
            'message'        => $this->message,
            'application_id' => $this->applicationId,
            'job_id'         => $this->jobId,
            'job_title'      => $this->jobTitle,
            'company_name'   => $this->companyName,
            'reason'         => $this->reason,
            'amount'         => $this->amount,
            'comment_body'   => $this->commentBody,
        ], fn ($v) => $v !== null);
    }
}
