<?php

namespace App\Domain\Events;

class UserRegistered
{
    public function __construct(
        public readonly int $userId,
        public readonly string $role,
        public readonly string $name,
    ) {}
}
