<?php

namespace Database\Factories;

use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Models\QueueEntry;
use App\Models\QueueEntryStatusChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueEntryStatusChange>
 */
class QueueEntryStatusChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'queue_entry_id' => QueueEntry::factory(),
            'from_status' => null,
            'to_status' => QueueEntryStatus::Waiting,
            'actor' => StatusChangeActor::Visitor,
            'changed_by_user_id' => null,
            'service_point_id' => null,
            'created_at' => now(),
        ];
    }
}
