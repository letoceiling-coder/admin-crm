<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-' . strtoupper(fake()->bothify('########')),
            'payer_name' => fake()->name(),
            'payer_email' => fake()->email(),
            'payer_phone' => fake()->phoneNumber(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'payment_method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'online', 'other']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'refunded']),
            'payment_date' => fake()->dateTime(),
        ];
    }
}
