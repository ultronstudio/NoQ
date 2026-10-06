<?php

namespace App\Actions\Queue;

use App\Actions\Queue\Concerns\TransitionsQueueEntryStatus;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Models\QueueEntry;
use App\Models\User;

/**
 * Cancels a Waiting or Called entry. The actor is explicit because a
 * cancellation can come from the visitor themselves or from an operator.
 */
final class CancelEntry
{
    use TransitionsQueueEntryStatus;

    public function __invoke(QueueEntry $entry, StatusChangeActor $actor = StatusChangeActor::Visitor, ?User $operator = null): QueueEntry
    {
        return $this->transition(
            entry: $entry,
            to: QueueEntryStatus::Cancelled,
            actor: $actor,
            changedBy: $operator,
        );
    }
}
