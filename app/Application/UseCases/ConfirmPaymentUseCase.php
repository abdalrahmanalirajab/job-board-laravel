<?php

namespace App\Application\UseCases;

use App\Application\DTOs\ConfirmPaymentInput;
use App\Application\DTOs\ConfirmPaymentOutput;
use App\Application\Interfaces\PaymentApplicationInterface;
use App\Application\Interfaces\PaymentEventDispatcherInterface;
use App\Application\Interfaces\PaymentGatewayInterface;
use App\Application\Interfaces\PaymentRepositoryInterface;
use App\Domain\Events\PaymentCompleted;
use App\Domain\Exceptions\PaymentException;
use App\Domain\Exceptions\UnauthorizedPaymentException;

class ConfirmPaymentUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentEventDispatcherInterface $eventDispatcher,
        private readonly PaymentApplicationInterface $applicationService,
    ) {}

    public function execute(ConfirmPaymentInput $input): ConfirmPaymentOutput
    {
        $session = $this->paymentGateway->retrieveCheckoutSession($input->sessionId);

        $paymentIntentId = $session['payment_intent_id'];
        $paymentIntent = $this->paymentGateway->retrievePaymentIntent($paymentIntentId);

        if ($paymentIntent['status'] !== 'succeeded') {
            throw new PaymentException('Payment has not succeeded on Stripe');
        }

        $payment = $this->paymentRepository->findByStripePaymentIntentId($paymentIntentId);
        if ($payment === null) {
            $payment = $this->paymentRepository->findBySessionId($input->sessionId);
        }

        if ($payment === null) {
            throw new PaymentException('Payment not found');
        }

        if ($payment->employerId() !== $input->employerId) {
            throw new UnauthorizedPaymentException();
        }

        $payment->complete($paymentIntentId);
        $this->paymentRepository->update($payment);

        $this->applicationService->markAsPaid($payment->applicationId());

        $this->eventDispatcher->dispatchCompleted(new PaymentCompleted(
            paymentId: $payment->id(),
            applicationId: $payment->applicationId(),
            employerId: $payment->employerId(),
            amount: $payment->amount(),
            paidAt: $payment->paidAt(),
        ));

        return new ConfirmPaymentOutput(
            status: $payment->status()->value,
            paidAt: $payment->paidAt()->format('c'),
        );
    }
}
