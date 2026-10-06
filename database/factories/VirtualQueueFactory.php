<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VirtualQueue>
 */
class VirtualQueueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'description' => fake()->boolean(70) ? fake()->sentence() : null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
