<?php

namespace App\Exceptions\Queue;

use App\Enums\QueueEntryStatus;

/**
 * Raised when a QueueEntry status transition is not in its allowed set
 * (see App\Enums\QueueEntryStatus::allowedTransitions()).
 */
final class InvalidQueueEntryTransitionException extends QueueDomainException
{
    public static function fromTo(QueueEntryStatus $from, QueueEntryStatus $to): self
    {
        return new self("Cannot transition queue entry from [{$from->value}] to [{$to->value}].");
    }
}
