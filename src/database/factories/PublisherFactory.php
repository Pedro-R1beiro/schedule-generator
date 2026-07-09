<?php

namespace Database\Factories;

use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publisher>
 */
class PublishersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'phone' => $this->faker->phoneNumber,
            'is_active' => true,
            'is_manual' => false,
            'monthly_limit' => 4,
            'weekly_limit' => 2,
            'is_pioneer' => false,
            'gender' => $this->faker->randomElement(['M', 'F']),
            'start_day' => $this->faker->numberBetween(1, 31),
        ];
    }
}
