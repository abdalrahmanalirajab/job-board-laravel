<?php

namespace App\Application\UseCases;

use App\Application\DTOs\PaymentStatusOutput;
use App\Application\Interfaces\PaymentApplicationInterface;
use App\Application\Interfaces\PaymentRepositoryInterface;
use App\Domain\Exceptions\PaymentException;
use App\Domain\Exceptions\UnauthorizedPaymentException;

class GetPaymentStatusUseCase
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentApplicationInterface $applicationService,
    ) {}

    public function execute(int $paymentId, int $employerId): PaymentStatusOutput
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if ($payment === null) {
            throw new PaymentException('Payment not found');
        }

        if ($payment->employerId() !== $employerId) {
            throw new UnauthorizedPaymentException();
        }

        $application = $this->applicationService->getApplication($payment->applicationId());

        return new PaymentStatusOutput(
            paymentId: $payment->id(),
            paymentStatus: $payment->status()->value,
            applicationStatus: $application->status,
            amount: $payment->amount()->amount(),
            currency: $payment->amount()->currency(),
            paidAt: $payment->paidAt()?->format('c'),
        );
    }
}
