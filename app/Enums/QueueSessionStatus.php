<?php

namespace App\Enums;

enum QueueSessionStatus: string
{
    case Open = 'open';
    case Paused = 'paused';
    case Closed = 'closed';

    /**
     * Sessions in these statuses count as the "active" session of a VirtualQueue.
     * A VirtualQueue may have at most one session in one of these statuses at a time.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::Open, self::Paused => true,
            self::Closed => false,
        };
    }
}
