<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => 50.00,
            'currency' => 'USD',
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            'stripe_client_secret' => 'pi_secret_' . fake()->regexify('[a-zA-Z0-9]{24}'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'paid_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }
}