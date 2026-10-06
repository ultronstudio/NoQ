<?php

namespace Database\Factories;

use App\Models\ServicePoint;
use App\Models\VirtualQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePoint>
 */
class ServicePointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'virtual_queue_id' => VirtualQueue::factory(),
            'name' => 'Přepážka '.fake()->unique()->numberBetween(1, 1000),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
