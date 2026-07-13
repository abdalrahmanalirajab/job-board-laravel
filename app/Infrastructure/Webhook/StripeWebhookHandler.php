<?php

namespace App\Infrastructure\Webhook;

use App\Application\UseCases\HandleStripeWebhookUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;

class StripeWebhookHandler
{
    public function __construct(
        private readonly HandleStripeWebhookUseCase $useCase,
        private readonly Logger $logger,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature');

        if (empty($signatureHeader)) {
            return response()->json(['error' => 'Missing Stripe signature header'], 400);
        }

        try {
            $this->useCase->execute($payload, $signatureHeader);
            return response()->json(['status' => 'ok']);
        } catch (\UnexpectedValueException $e) {
            $this->logger->warning('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->warning('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
