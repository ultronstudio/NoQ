<?php

namespace App\Actions\Queue;

use App\Actions\Queue\Concerns\TransitionsQueueEntryStatus;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Models\QueueEntry;
use App\Models\User;

/**
 * Marks a Called entry as Completed once the visitor has been served.
 */
final class CompleteEntry
{
    use TransitionsQueueEntryStatus;

    public function __invoke(QueueEntry $entry, ?User $operator = null): QueueEntry
    {
        return $this->transition(
            entry: $entry,
            to: QueueEntryStatus::Completed,
            actor: StatusChangeActor::Operator,
            changedBy: $operator,
        );
    }
}
