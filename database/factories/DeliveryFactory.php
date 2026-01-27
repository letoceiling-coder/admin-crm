<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_number' => 'DEL-' . strtoupper(fake()->bothify('########')),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->phoneNumber(),
            'delivery_address' => fake()->address(),
            'status' => fake()->randomElement(['pending', 'in_transit', 'delivered', 'cancelled']),
            'delivery_date' => fake()->dateTime(),
        ];
    }
}
