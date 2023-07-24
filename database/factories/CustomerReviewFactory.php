<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerReview>
 */
class CustomerReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_type' => fake()->randomElement(['sales', 'service']),
            'date' => fake()->date(),
            'customer_number' => fake()->randomNumber(5),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'sent_type' => fake()->randomElement(['sms', 'email']),
        ];
    }
}
