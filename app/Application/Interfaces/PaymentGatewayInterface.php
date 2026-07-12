<?php

namespace App\Application\Interfaces;

use App\Domain\Entities\Payment;

interface PaymentGatewayInterface
{
    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): array;

    public function retrieveCheckoutSession(string $sessionId): array;

    public function retrievePaymentIntent(string $paymentIntentId): array;

    public function verifyWebhookSignature(string $payload, string $signatureHeader): \Stripe\Event;
}
