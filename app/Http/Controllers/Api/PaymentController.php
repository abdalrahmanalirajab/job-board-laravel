<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;
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
                'success_url'    => 'required|url',
                'cancel_url'     => 'required|url',
            ]);

            $application = Application::find($request->application_id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.',
                    'data'    => null,
                ], 404);
            }

            $application->load(['jobListing.employer', 'jobListing.category']);

            if ((int) $application->jobListing->employer_id !== (int) auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to make this payment',
                    'data'    => null,
                ], 403);
            }

            if ($application->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only pay for accepted applications',
                    'data'    => null,
                ], 422);
            }

            if ($application->payment()->where('status', 'completed')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been made for this application',
                    'data'    => null,
                ], 422);
            }

            $application->payment()->where('status', '!=', 'completed')->delete();

            $amount = $application->jobListing->salary_max
                ? (float) $application->jobListing->salary_max
                : 50.00;

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment amount',
                    'data'    => null,
                ], 422);
            }

            $payment = DB::transaction(function () use ($application, $amount, $request) {
                $payment = Payment::create([
                    'employer_id'    => auth()->id(),
                    'application_id' => $application->id,
                    'amount'         => $amount,
                    'currency'       => 'USD',
                    'provider'       => 'stripe',
                    'status'         => 'pending',
                ]);

                Stripe::setApiKey(config('services.stripe.secret'));

                $session = StripeCheckoutSession::create([
                    'mode'        => 'payment',
                    'line_items'  => [[
                        'price_data' => [
                            'currency'     => 'usd',
                            'product_data' => [
                                'name' => 'Job Application Fee - ' . $application->jobListing->title,
                            ],
                            'unit_amount' => (int) ($amount * 100),
                        ],
                        'quantity'   => 1,
                    ]],
                    'metadata'  => [
                        'application_id'  => (string) $application->id,
                        'employer_id'     => (string) auth()->id(),
                        'job_title'       => $application->jobListing->title,
                        'candidate_email' => $application->contact_email,
                    ],
                    'success_url' => $request->success_url,
                    'cancel_url'  => $request->cancel_url,
                ]);

                $payment->update([
                    'stripe_session_id' => $session->id,
                ]);

                return [$payment, $session];
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data'    => [
                    'checkout_url' => $payment[1]->url,
                    'payment_id'   => $payment[0]->id,
                    'session_id'   => $payment[0]->stripe_session_id,
                    'amount'       => $amount,
                    'currency'     => 'USD',
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Confirm a payment after the user returns from Stripe Checkout.
     */
    public function confirm(Request $request)
    {
        try {
            $request->validate(['session_id' => 'required|string']);

            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeCheckoutSession::retrieve($request->session_id);

            $payment = Payment::where('stripe_session_id', $session->id)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.',
                    'data'    => null,
                ], 404);
            }

            if ($payment->employer_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ], 403);
            }

            $status = match ($session->payment_status) {
                'paid'       => 'completed',
                'unpaid'     => 'pending',
                'no_payment_required' => 'completed',
                default      => $payment->status,
            };

            if ($status === 'completed' && $payment->status !== 'completed') {
                $payment->update([
                    'status'                   => 'completed',
                    'paid_at'                  => now(),
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);

                $application = $payment->application;
                if ($application && $application->status !== 'paid') {
                    $application->update(['status' => 'paid']);
                }
            } elseif ($session->status === 'expired') {
                $payment->update(['status' => 'failed']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated.',
                'data'    => [
                    'status'  => $payment->fresh()->status,
                    'session' => [
                        'id'              => $session->id,
                        'payment_status'  => $session->payment_status,
                        'payment_intent'  => $session->payment_intent,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Get the status of a payment by its local ID.
     */
    public function status(int $id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found.',
                    'data'    => null,
                ], 404);
            }

            if ($payment->employer_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status retrieved.',
                'data'    => [
                    'payment_status' => $payment->status,
                    'paid_at'        => $payment->paid_at,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment status: ' . $e->getMessage(),
                'data'    => null,
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

        $payment = Payment::where('stripe_session_id', $session->id)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            Log::warning('Stripe webhook: checkout.session.completed - no matching payment', [
                'session_id' => $session->id,
                'event_id'   => $eventId,
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

        $paymentIntentId = $session->payment_intent ?? null;

        $update = [
            'status'  => 'completed',
            'paid_at' => now(),
        ];

        if ($paymentIntentId) {
            $update['stripe_payment_intent_id'] = $paymentIntentId;
        }

        $payment->update($update);

        $application = $payment->application;
        if ($application && $application->status !== 'paid') {
            $application->update(['status' => 'paid']);
        }

        Log::info('Stripe webhook: checkout session completed', [
            'payment_id'               => $payment->id,
            'session_id'               => $session->id,
            'stripe_payment_intent_id' => $paymentIntentId,
            'application_status'       => $application?->status,
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
                'message' => 'Failed to retrieve payments: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
