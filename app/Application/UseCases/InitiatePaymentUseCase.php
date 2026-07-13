<?php

namespace App\Application\UseCases;

use App\Application\DTOs\InitiatePaymentInput;
use App\Application\DTOs\InitiatePaymentOutput;
use App\Application\Interfaces\PaymentApplicationInterface;
use App\Application\Interfaces\PaymentEventDispatcherInterface;
use App\Application\Interfaces\PaymentGatewayInterface;
use App\Application\Interfaces\PaymentRepositoryInterface;
use App\Domain\Entities\Payment;
use App\Domain\Events\PaymentInitiated;
use App\Domain\Exceptions\DuplicatePaymentException;
use App\Domain\Exceptions\InvalidPaymentStateException;
use App\Domain\Exceptions\UnauthorizedPaymentException;
use App\Domain\ValueObjects\Money;

class InitiatePaymentUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentEventDispatcherInterface $eventDispatcher,
        private readonly PaymentApplicationInterface $applicationService,
    ) {}

    public function execute(InitiatePaymentInput $input): InitiatePaymentOutput
    {
        $application = $this->applicationService->getApplication($input->applicationId);

        if ($application->status !== 'accepted') {
            throw new InvalidPaymentStateException('Application must be accepted before payment');
        }

        if ($application->employerId !== $input->employerId) {
            throw new UnauthorizedPaymentException('You do not own this application');
        }

        if ($this->paymentRepository->existsForApplication($input->applicationId)) {
            throw new DuplicatePaymentException($input->applicationId);
        }

        $money = new Money(50.00);
        $payment = new Payment(
            employerId: $input->employerId,
            applicationId: $input->applicationId,
            amount: $money,
        );

        $session = $this->paymentGateway->createCheckoutSession(
            payment: $payment,
            successUrl: $input->successUrl,
            cancelUrl: $input->cancelUrl,
        );

        $payment->setStripeSessionId($session['session_id']);
        $payment = $this->paymentRepository->save($payment);

        $this->eventDispatcher->dispatchInitiated(new PaymentInitiated(
            paymentId: $payment->id(),
            applicationId: $payment->applicationId(),
            employerId: $payment->employerId(),
            amount: $payment->amount(),
        ));

        return new InitiatePaymentOutput(
            checkoutUrl: $session['checkout_url'],
            paymentId: $payment->id(),
        );
    }
}
