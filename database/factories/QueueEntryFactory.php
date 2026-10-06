<?php

namespace Database\Factories;

use App\Enums\QueueEntryStatus;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Support\Queue\TrackingToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueEntry>
 */
class QueueEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'queue_session_id' => QueueSession::factory(),
            'service_point_id' => null,
            'tracking_token_hash' => TrackingToken::generate()->hash,
            'number' => fake()->unique()->numberBetween(1, 1_000_000),
            'status' => QueueEntryStatus::Waiting,
        ];
    }

    public function called(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QueueEntryStatus::Called,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QueueEntryStatus::Completed,
        ]);
    }
}
