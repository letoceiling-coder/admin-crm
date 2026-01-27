<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper(fake()->bothify('########')),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->email(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_address' => fake()->address(),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'order_date' => fake()->dateTime(),
        ];
    }
}
