<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentController extends Controller
{
    /**
     * Initiate a payment checkout for an accepted application.
     * Middleware: auth:sanctum + employer
     */
    public function checkout(Request $request)
    {
        try {
            $request->validate([
                'application_id' => 'required|exists:applications,id',
            ]);

            $application = Application::find($request->application_id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.',
                    'data'    => null,
                ], 404);
            }

            // Eager load relationships to avoid N+1 query problems
            $application->load(['jobListing.employer', 'jobListing.category']);

            // Verify the job belongs to the authenticated employer
            if ((int) $application->jobListing->employer_id !== (int) auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to make this payment',
                    'data'    => null,
                ], 403);
            }

            // Application must be accepted
            if ($application->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only pay for accepted applications',
                    'data'    => null,
                ], 422);
            }

            // Check no completed payment already exists
            if ($application->payment()->where('status', 'completed')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been made for this application',
                    'data'    => null,
                ], 422);
            }

            // Delete any previous pending/failed payment for this application
            $application->payment()->where('status', '!=', 'completed')->delete();

            // Calculate amount: 10% of salary_max (platform fee), minimum $1.00
            $amount = $application->jobListing->salary_max
                ? round((float) $application->jobListing->salary_max * 0.10, 2)
                : 50.00;

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment amount',
                    'data'    => null,
                ], 422);
            }

            // Wrap in DB transaction: create Payment record BEFORE Stripe PaymentIntent
            // so the webhook can always find a matching record (race condition fix)
            $payment = DB::transaction(function () use ($application, $amount) {
                // 1. Create Payment record in DB first (no stripe IDs yet)
                $payment = Payment::create([
                    'employer_id'    => auth()->id(),
                    'application_id' => $application->id,
                    'amount'         => $amount,
                    'currency'       => 'USD',
                    'provider'       => 'stripe',
                    'status'         => 'pending',
                ]);

                // 2. Initialize Stripe and create PaymentIntent
                Stripe::setApiKey(config('services.stripe.secret'));

                $paymentIntent = PaymentIntent::create([
                    'amount'   => (int) ($amount * 100),
                    'currency' => 'usd',
                    'metadata' => [
                        'application_id'  => $application->id,
                        'employer_id'     => auth()->id(),
                        'job_title'       => $application->jobListing->title,
                        'candidate_email' => $application->contact_email,
                    ],
                ]);

                // 3. Update the existing Payment record with Stripe IDs
                $payment->update([
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_client_secret'     => $paymentIntent->client_secret,
                ]);

                return $payment;
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data'    => [
                    'client_secret'          => $payment->stripe_client_secret,
                    'payment_intent_id'      => $payment->stripe_payment_intent_id,
                    'amount'                 => $amount,
                    'currency'               => 'USD',
                    'stripe_publishable_key' => config('services.stripe.key'),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
            ], 500);
        }
    }

    /**
     * Handle Stripe webhook events.
     */
    public function stripeWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook: invalid payload', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventId   = $event->id;
        $eventType = $event->type;

        Log::info('Stripe webhook received', [
            'event_id'   => $eventId,
            'event_type' => $eventType,
        ]);

        try {
            DB::beginTransaction();

            match ($eventType) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event, $eventId),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event, $eventId),
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event, $eventId),
                default => Log::info('Stripe webhook: unhandled event type', [
                    'event_type' => $eventType,
                    'event_id'   => $eventId,
                ]),
            };

            DB::commit();

            return response()->json(['received' => true], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Stripe webhook: processing failed', [
                'event_id'   => $eventId,
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function findPaymentByIntent(string $paymentIntentId, mixed $paymentIntent): ?Payment
    {
        // Try primary lookup by stripe_payment_intent_id
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
            ->lockForUpdate()
            ->first();

        if ($payment) {
            return $payment;
        }

        // Fallback: lookup by application_id from metadata (race condition safety net)
        $applicationId = $paymentIntent->metadata->application_id ?? null;
        if ($applicationId) {
            $payment = Payment::where('application_id', $applicationId)
                ->lockForUpdate()
                ->first();

            if ($payment) {
                // Update the stripe_payment_intent_id for future lookups
                $payment->update(['stripe_payment_intent_id' => $paymentIntentId]);
            }
        }

        return $payment;
    }

    private function handlePaymentIntentSucceeded(mixed $event, string $eventId): void
    {
        $paymentIntent = $event->data->object;
        $payment = $this->findPaymentByIntent($paymentIntent->id, $paymentIntent);

        if (!$payment) {
            Log::warning('Stripe webhook: payment_intent.succeeded - no matching payment', [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'event_id'                 => $eventId,
            ]);
            return;
        }

        if ($payment->status === 'completed') {
            Log::info('Stripe webhook: payment_intent.succeeded - already completed', [
                'payment_id' => $payment->id,
                'event_id'   => $eventId,
            ]);
            return;
        }

        $payment->update([
            'status'  => 'completed',
            'paid_at' => now(),
        ]);

        Log::info('Stripe webhook: payment completed', [
            'payment_id'               => $payment->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'event_id'                 => $eventId,
        ]);
    }

    private function handlePaymentIntentFailed(mixed $event, string $eventId): void
    {
        $paymentIntent = $event->data->object;
        $payment = $this->findPaymentByIntent($paymentIntent->id, $paymentIntent);

        if (!$payment) {
            Log::warning('Stripe webhook: payment_intent.payment_failed - no matching payment', [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'event_id'                 => $eventId,
            ]);
            return;
        }

        if ($payment->status === 'failed') {
            Log::info('Stripe webhook: payment_intent.payment_failed - already failed', [
                'payment_id' => $payment->id,
                'event_id'   => $eventId,
            ]);
            return;
        }

        $payment->update(['status' => 'failed']);

        Log::info('Stripe webhook: payment marked as failed', [
            'payment_id'               => $payment->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'event_id'                 => $eventId,
        ]);
    }

    private function handleCheckoutSessionCompleted(mixed $event, string $eventId): void
    {
        $session = $event->data->object;
        $paymentIntentId = $session->payment_intent ?? null;

        if (!$paymentIntentId) {
            Log::warning('Stripe webhook: checkout.session.completed - no payment_intent in session', [
                'session_id' => $session->id,
                'event_id'   => $eventId,
            ]);
            return;
        }

        // Checkout session doesn't carry metadata directly, so only primary lookup
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            Log::warning('Stripe webhook: checkout.session.completed - no matching payment', [
                'stripe_payment_intent_id' => $paymentIntentId,
                'session_id'               => $session->id,
                'event_id'                 => $eventId,
            ]);
            return;
        }

        if ($payment->status === 'completed') {
            Log::info('Stripe webhook: checkout.session.completed - already completed', [
                'payment_id' => $payment->id,
                'event_id'   => $eventId,
            ]);
            return;
        }

        $payment->update([
            'status'  => 'completed',
            'paid_at' => now(),
        ]);

        Log::info('Stripe webhook: checkout session completed', [
            'payment_id'               => $payment->id,
            'session_id'               => $session->id,
            'stripe_payment_intent_id' => $paymentIntentId,
            'event_id'                 => $eventId,
        ]);
    }

    /**
     * List payments for the authenticated employer.
     * Middleware: auth:sanctum + employer
     */
    public function myPayments(Request $request)
    {
        try {
            $payments = Payment::where('employer_id', auth()->id())
                ->with(['application.jobListing', 'application.candidate'])
                ->latest()
                ->paginate(10);

            return PaymentResource::collection($payments)->additional([
                'success' => true,
                'message' => 'Payments retrieved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments.',
                'data'    => null,
            ], 500);
        }
    }
}
