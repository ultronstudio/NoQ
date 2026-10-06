<?php

namespace App\Policies;

use App\Models\QueueEntry;
use App\Models\User;

/**
 * A QueueEntry belongs to an anonymous visitor, not a User. Authorization
 * for operators is derived from the VirtualQueue behind the entry's
 * session.
 */
class QueueEntryPolicy
{
    public function view(User $user, QueueEntry $entry): bool
    {
        return $entry->session->virtualQueue->user_id === $user->id;
    }

    /**
     * Covers operator actions on an entry (call/complete/cancel/no-show).
     */
    public function manage(User $user, QueueEntry $entry): bool
    {
        return $entry->session->virtualQueue->user_id === $user->id;
    }
}
