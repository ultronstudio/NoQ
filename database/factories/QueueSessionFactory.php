<?php

namespace Database\Factories;

use App\Enums\QueueSessionStatus;
use App\Models\QueueSession;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueSession>
 */
class QueueSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'virtual_queue_id' => VirtualQueue::factory(),
            'opened_by_user_id' => User::factory(),
            'closed_by_user_id' => null,
            'status' => QueueSessionStatus::Open,
            'next_number' => 1,
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QueueSessionStatus::Paused,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QueueSessionStatus::Closed,
            'closed_at' => now(),
            'closed_by_user_id' => User::factory(),
        ]);
    }
}
