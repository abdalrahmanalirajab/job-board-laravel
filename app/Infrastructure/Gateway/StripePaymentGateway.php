<?php

namespace App\Infrastructure\Gateway;

use App\Application\Interfaces\PaymentGatewayInterface;
use App\Domain\Entities\Payment;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGatewayInterface
{
    private const FLAT_FEE_CENTS = 5000;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        $session = Session::create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->amount()->currency()),
                    'product_data' => [
                        'name' => 'Application Processing Fee',
                        'description' => "Fee for application #{$payment->applicationId()}",
                    ],
                    'unit_amount' => $payment->amount()->inCents(),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'application_id' => (string) $payment->applicationId(),
                'employer_id' => (string) $payment->employerId(),
            ],
        ]);

        return [
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $session = Session::retrieve($sessionId);

        return [
            'id' => $session->id,
            'payment_intent_id' => $session->payment_intent,
            'status' => $session->status,
            'metadata' => $session->metadata->toArray(),
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        return [
            'id' => $intent->id,
            'status' => $intent->status,
            'amount' => $intent->amount,
            'currency' => $intent->currency,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): Event
    {
        $endpointSecret = config('services.stripe.webhook_secret');

        return Webhook::constructEvent($payload, $signatureHeader, $endpointSecret);
    }
}
