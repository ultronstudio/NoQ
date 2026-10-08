<?php

namespace App\Policies;

use App\Models\QueueEntry;
use App\Models\User;

/**
 * A QueueEntry belongs to an anonymous visitor, not a User. Authorization
 * for operators is derived from the VirtualQueue behind the entry's
 * session: a supervisor may act on any entry, a service point worker only
 * on entries already assigned to their own point(s) (see
 * App\Models\User::canManageEntry()).
 */
class QueueEntryPolicy
{
    public function view(User $user, QueueEntry $entry): bool
    {
        return $user->canViewQueue($entry->session->virtualQueue);
    }

    /**
     * Covers operator actions on an entry (complete/cancel/no-show).
     * Calling an entry to a service point is authorized separately via
     * ServicePointPolicy::operate, since a Waiting entry has no
     * service_point_id yet to derive this from.
     */
    public function manage(User $user, QueueEntry $entry): bool
    {
        return $user->canManageEntry($entry);
    }
}
