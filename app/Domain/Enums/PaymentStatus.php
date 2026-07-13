<?php

namespace App\Domain\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }
}
