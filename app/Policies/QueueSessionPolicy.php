<?php

namespace App\Policies;

use App\Models\QueueSession;
use App\Models\User;

/**
 * A QueueSession has no owner of its own: authorization is derived from
 * the VirtualQueue it belongs to. Session lifecycle control (open/pause/
 * resume/close/callNext) is supervisor-level: service point workers never
 * manage the session itself, only entries at their own point.
 */
class QueueSessionPolicy
{
    public function view(User $user, QueueSession $session): bool
    {
        return $user->isSupervisorOf($session->virtualQueue);
    }

    /**
     * Covers the full session lifecycle (open/pause/resume/close/callNext).
     */
    public function manage(User $user, QueueSession $session): bool
    {
        return $user->isSupervisorOf($session->virtualQueue);
    }
}
