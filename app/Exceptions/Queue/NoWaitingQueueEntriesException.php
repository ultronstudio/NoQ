<?php

namespace App\Exceptions\Queue;

use App\Models\QueueSession;

/**
 * Raised by CallNextEntry when a session has no waiting entries left.
 */
final class NoWaitingQueueEntriesException extends QueueDomainException
{
    public static function forSession(QueueSession $session): self
    {
        return new self("Queue session [{$session->getKey()}] has no waiting entries to call.");
    }
}
