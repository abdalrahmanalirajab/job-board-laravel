<?php

namespace App\Application\UseCases;

use App\Application\Interfaces\PaymentApplicationInterface;
use App\Application\Interfaces\PaymentEventDispatcherInterface;
use App\Application\Interfaces\PaymentGatewayInterface;
use App\Application\Interfaces\PaymentRepositoryInterface;
use App\Domain\Events\PaymentCompleted;
use App\Domain\Exceptions\PaymentException;
use Illuminate\Log\Logger;

class HandleStripeWebhookUseCase
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentEventDispatcherInterface $eventDispatcher,
        private readonly PaymentApplicationInterface $applicationService,
        private readonly Logger $logger,
    ) {}

    public function execute(string $payload, string $signatureHeader): void
    {
        $event = $this->paymentGateway->verifyWebhookSignature($payload, $signatureHeader);

        if ($event->type !== 'checkout.session.completed' && $event->type !== 'payment_intent.succeeded') {
            $this->logger->info('Ignoring unhandled webhook event type', ['type' => $event->type]);
            return;
        }

        $sessionId = null;
        $paymentIntentId = null;

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $sessionId = $session->id;
            $paymentIntentId = $session->payment_intent;
        } elseif ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $paymentIntentId = $paymentIntent->id;
        }

        if ($paymentIntentId === null) {
            $this->logger->warning('Webhook event missing payment intent', ['event_id' => $event->id]);
            return;
        }

        $payment = $this->paymentRepository->findByStripePaymentIntentId($paymentIntentId);
        if ($payment === null && $sessionId !== null) {
            $payment = $this->paymentRepository->findBySessionId($sessionId);
        }

        if ($payment === null) {
            $this->logger->warning('Payment not found for webhook', [
                'payment_intent_id' => $paymentIntentId,
                'session_id' => $sessionId,
            ]);
            return;
        }

        if ($payment->status()->isCompleted()) {
            $this->logger->info('Payment already completed, skipping webhook', [
                'payment_id' => $payment->id(),
            ]);
            return;
        }

        $payment->complete($event->id);
        $this->paymentRepository->update($payment);

        $this->applicationService->markAsPaid($payment->applicationId());

        $this->eventDispatcher->dispatchCompleted(new PaymentCompleted(
            paymentId: $payment->id(),
            applicationId: $payment->applicationId(),
            employerId: $payment->employerId(),
            amount: $payment->amount(),
            paidAt: $payment->paidAt(),
        ));

        $this->logger->info('Payment completed via webhook', [
            'payment_id' => $payment->id(),
            'event_id' => $event->id,
        ]);
    }
}
