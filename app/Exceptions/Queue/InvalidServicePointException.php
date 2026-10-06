<?php

namespace App\Exceptions\Queue;

use App\Models\QueueSession;
use App\Models\ServicePoint;

/**
 * Raised when a ServicePoint used to serve an entry does not belong to the
 * same VirtualQueue as the session, or is not active.
 */
final class InvalidServicePointException extends QueueDomainException
{
    public static function notInQueue(ServicePoint $servicePoint, QueueSession $session): self
    {
        return new self(
            "Service point [{$servicePoint->getKey()}] does not belong to the same virtual queue as session [{$session->getKey()}]."
        );
    }

    public static function inactive(ServicePoint $servicePoint): self
    {
        return new self("Service point [{$servicePoint->getKey()}] is not active.");
    }
}
