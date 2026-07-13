<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'contact_email' => fake()->email(),
            'contact_phone' => fake()->phoneNumber(),
            'applied_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'pending']);
    }

    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'accepted']);
    }

    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => fake()->randomElement([
                'Position has been filled internally.',
                'Skills do not match current requirements.',
                'Seeking more senior-level experience.',
                'Budget constraints for this position.',
                'Role requirements have changed.',
            ]),
        ]);
    }
}