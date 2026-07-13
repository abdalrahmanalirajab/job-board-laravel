<?php

namespace App\Domain\ValueObjects;

class Money
{
    public function __construct(
        private readonly float $amount,
        private readonly string $currency = 'USD',
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        if (empty($currency)) {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function inCents(): int
    {
        return (int) round($this->amount * 100);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
