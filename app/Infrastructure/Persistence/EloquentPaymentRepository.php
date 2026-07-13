<?php

namespace App\Infrastructure\Persistence;

use App\Application\Interfaces\PaymentRepositoryInterface;
use App\Domain\Entities\Payment;
use App\Domain\Enums\PaymentStatus;
use App\Domain\ValueObjects\Money;
use App\Models\Payment as PaymentModel;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function save(Payment $payment): Payment
    {
        $model = new PaymentModel();
        $model->employer_id = $payment->employerId();
        $model->application_id = $payment->applicationId();
        $model->amount = $payment->amount()->amount();
        $model->currency = $payment->amount()->currency();
        $model->provider = $payment->provider();
        $model->stripe_payment_intent_id = $payment->stripePaymentIntentId();
        $model->stripe_client_secret = $payment->stripeClientSecret();
        $model->stripe_session_id = $payment->stripeSessionId();
        $model->status = $payment->status()->value;
        $model->save();

        $payment->setId($model->id);

        return $payment;
    }

    public function update(Payment $payment): void
    {
        $model = PaymentModel::findOrFail($payment->id());
        $model->stripe_payment_intent_id = $payment->stripePaymentIntentId();
        $model->stripe_client_secret = $payment->stripeClientSecret();
        $model->stripe_session_id = $payment->stripeSessionId();
        $model->status = $payment->status()->value;
        $model->paid_at = $payment->paidAt();
        $model->stripe_event_id = $payment->stripeEventId();
        $model->save();
    }

    public function findById(int $id): ?Payment
    {
        $model = PaymentModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByApplicationId(int $applicationId): ?Payment
    {
        $model = PaymentModel::where('application_id', $applicationId)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByStripePaymentIntentId(string $paymentIntentId): ?Payment
    {
        $model = PaymentModel::where('stripe_payment_intent_id', $paymentIntentId)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findBySessionId(string $sessionId): ?Payment
    {
        $model = PaymentModel::where('stripe_session_id', $sessionId)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmployerId(int $employerId): array
    {
        return PaymentModel::where('employer_id', $employerId)
            ->get()
            ->map(fn (PaymentModel $model) => $this->toDomain($model))
            ->all();
    }

    public function existsForApplication(int $applicationId): bool
    {
        return PaymentModel::where('application_id', $applicationId)->exists();
    }

    private function toDomain(PaymentModel $model): Payment
    {
        $payment = new Payment(
            employerId: $model->employer_id,
            applicationId: $model->application_id,
            amount: new Money((float) $model->amount, $model->currency ?? 'USD'),
            provider: $model->provider ?? 'stripe',
            stripePaymentIntentId: $model->stripe_payment_intent_id,
            stripeClientSecret: $model->stripe_client_secret,
            stripeSessionId: $model->stripe_session_id,
            id: $model->id,
        );

        if ($model->status === 'completed') {
            $payment->complete($model->stripe_event_id ?? 'migrated');
        } elseif ($model->status === 'failed') {
            $payment->fail();
        }

        return $payment;
    }
}
