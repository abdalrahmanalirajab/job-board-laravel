<?php

namespace App\Application\Interfaces;

use App\Domain\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): Payment;

    public function update(Payment $payment): void;

    public function findById(int $id): ?Payment;

    public function findByApplicationId(int $applicationId): ?Payment;

    public function findByStripePaymentIntentId(string $paymentIntentId): ?Payment;

    public function findBySessionId(string $sessionId): ?Payment;

    public function findByEmployerId(int $employerId): array;

    public function existsForApplication(int $applicationId): bool;
}
