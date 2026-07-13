<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'candidate',
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'avatar' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
            'name' => fake()->name(),
        ]);
    }

    public function employer(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'employer',
            'name' => fake()->name(),
        ]);
    }

    public function candidate(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'candidate',
            'name' => fake()->name(),
        ]);
    }
}