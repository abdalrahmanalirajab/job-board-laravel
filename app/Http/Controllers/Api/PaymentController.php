<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $application = Application::findOrFail($request->application_id);

        if ($application->jobListing->employer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to pay for this application.',
                'data' => null,
            ], 403);
        }

        if ($application->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Payment can only be initiated for accepted applications.',
                'data' => null,
            ], 422);
        }

        if ($application->payment()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A payment for this application has already been initiated.',
                'data' => null,
            ], 422);
        }

        $amount = 5000; // $50.00 in cents — fixed fee per accepted candidate

        $payment = Payment::create([
            'employer_id' => $request->user()->id,
            'application_id' => $application->id,
            'amount' => $amount,
            'currency' => 'usd',
            'provider' => 'stripe',
            'status' => 'pending',
        ]);

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $checkoutSession = \Stripe\Checkout\Session::create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "Payment for application #{$application->id} - {$application->jobListing->title}",
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'application_id' => (string) $application->id,
                ],
                'success_url' => config('app.url') . '/api/payments/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/api/payments/cancel',
            ]);

            $payment->update([
                'provider_payment_id' => $checkoutSession->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout session created successfully.',
                'data' => [
                    'payment' => new PaymentResource($payment),
                    'checkout_url' => $checkoutSession->url,
                    'session_id' => $checkoutSession->id,
                ],
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        if ($endpointSecret) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } catch (\UnexpectedValueException $e) {
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            $event = json_decode($payload);
        }

        switch ($event->type ?? '') {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $paymentId = $session->metadata->payment_id ?? null;

                if ($paymentId) {
                    $payment = Payment::find($paymentId);
                    if ($payment && $payment->status === 'pending') {
                        $payment->update([
                            'status' => 'completed',
                            'provider_payment_id' => $session->payment_intent ?? $session->id,
                            'paid_at' => now(),
                        ]);
                    }
                }
                break;
        }

        return response()->json(['received' => true]);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully.',
            'data' => ['session_id' => $sessionId],
        ]);
    }

    public function cancel()
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment was cancelled.',
            'data' => null,
        ]);
    }
}
