<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\Request;
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

            // Check no payment already exists
            if ($application->payment()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been made for this application',
                    'data'    => null,
                ], 422);
            }

            // Calculate amount: salary_max or default 50.00
            $amount = $application->jobListing->salary_max
                ? (float) $application->jobListing->salary_max
                : 50.00;

            // Initialize Stripe
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create Stripe PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount'   => (int) ($amount * 100), // convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'application_id'  => $application->id,
                    'employer_id'     => auth()->id(),
                    'job_title'       => $application->jobListing->title,
                    'candidate_email' => $application->contact_email,
                ],
            ]);

            // Create Payment record in DB
            Payment::create([
                'employer_id'               => auth()->id(),
                'application_id'            => $application->id,
                'amount'                    => $amount,
                'currency'                  => 'USD',
                'provider'                  => 'stripe',
                'stripe_payment_intent_id'  => $paymentIntent->id,
                'stripe_client_secret'      => $paymentIntent->client_secret,
                'status'                    => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data'    => [
                    'client_secret'          => $paymentIntent->client_secret,
                    'payment_intent_id'      => $paymentIntent->id,
                    'amount'                 => $amount,
                    'currency'               => 'USD',
                    'stripe_publishable_key' => config('services.stripe.key'),
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
     * Handle Stripe webhook events.
     * Route must have NO auth middleware.
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
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            $type = $event->type;

            switch ($type) {
                case 'payment_intent.succeeded':
                    $paymentIntentId = $event->data->object->id;
                    $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
                    if ($payment) {
                        $payment->update([
                            'status'  => 'completed',
                            'paid_at' => now(),
                        ]);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntentId = $event->data->object->id;
                    $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                        ]);
                    }
                    break;
            }

            return response()->json(['received' => true], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook handling failed: ' . $e->getMessage(),
            ], 500);
        }
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
