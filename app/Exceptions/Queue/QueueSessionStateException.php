<?php

namespace App\Exceptions\Queue;

use App\Models\QueueSession;
use App\Models\VirtualQueue;

/**
 * Raised when a QueueSession lifecycle action is attempted from a status
 * that does not allow it (open/pause/resume/close/join).
 */
final class QueueSessionStateException extends QueueDomainException
{
    public static function alreadyActive(VirtualQueue $virtualQueue): self
    {
        return new self("Virtual queue [{$virtualQueue->getKey()}] already has an active session.");
    }

    public static function notOpen(QueueSession $session): self
    {
        return new self("Queue session [{$session->getKey()}] is not open (status: {$session->status->value}).");
    }

    public static function cannotPause(QueueSession $session): self
    {
        return new self("Queue session [{$session->getKey()}] cannot be paused from status [{$session->status->value}].");
    }

    public static function cannotResume(QueueSession $session): self
    {
        return new self("Queue session [{$session->getKey()}] cannot be resumed from status [{$session->status->value}].");
    }

    public static function alreadyClosed(QueueSession $session): self
    {
        return new self("Queue session [{$session->getKey()}] is already closed and cannot be reopened.");
    }

    public static function full(QueueSession $session, int $maxWaiting): self
    {
        return new self("Queue session [{$session->getKey()}] already has {$maxWaiting} waiting entries (its configured maximum).");
    }
}
