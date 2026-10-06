<?php

namespace App\Actions\Queue;

use App\Actions\Queue\Concerns\TransitionsQueueEntryStatus;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Models\QueueEntry;
use App\Models\User;

/**
 * Marks a Waiting or Called entry as NoShow, e.g. after the visitor
 * failed to appear within a timeout.
 */
final class MarkEntryNoShow
{
    use TransitionsQueueEntryStatus;

    public function __invoke(QueueEntry $entry, ?User $operator = null): QueueEntry
    {
        return $this->transition(
            entry: $entry,
            to: QueueEntryStatus::NoShow,
            actor: StatusChangeActor::Operator,
            changedBy: $operator,
        );
    }
}
