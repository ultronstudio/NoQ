<?php

namespace App\Policies;

use App\Models\QueueSession;
use App\Models\User;

/**
 * A QueueSession has no owner of its own: authorization is derived from
 * the VirtualQueue it belongs to.
 */
class QueueSessionPolicy
{
    public function view(User $user, QueueSession $session): bool
    {
        return $session->virtualQueue->user_id === $user->id;
    }

    /**
     * Covers the full session lifecycle (open/pause/resume/close/callNext).
     */
    public function manage(User $user, QueueSession $session): bool
    {
        return $session->virtualQueue->user_id === $user->id;
    }
}
