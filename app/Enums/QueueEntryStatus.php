<?php

namespace App\Enums;

enum QueueEntryStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /**
     * Terminal statuses never allow a further transition.
     */
    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Statuses this status is allowed to transition into.
     * Called -> Waiting is intentionally not allowed yet.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Waiting => [self::Called, self::Cancelled, self::NoShow],
            self::Called => [self::Completed, self::Cancelled, self::NoShow],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
