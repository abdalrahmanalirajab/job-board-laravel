<?php

namespace App\Application\DTOs;

class ApplicationData
{
    public function __construct(
        public readonly int $id,
        public readonly int $employerId,
        public readonly string $status,
    ) {}
}
