<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VirtualQueue;

/**
 * Ownership plus role-based authorization. Full access ("update") is
 * granted to the queue's creator, its QueueSupervisor(s), and super
 * admins. "view" is broader: it also covers ServicePointWorker role
 * assignments, since workers need to open the queue page to serve their
 * own counter. See App\Models\User for the role-resolution logic and
 * docs/domain-model.md for the role model.
 */
class VirtualQueuePolicy
{
    public function view(User $user, VirtualQueue $virtualQueue): bool
    {
        return $user->canViewQueue($virtualQueue);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VirtualQueue $virtualQueue): bool
    {
        return $user->isSupervisorOf($virtualQueue);
    }

    public function delete(User $user, VirtualQueue $virtualQueue): bool
    {
        return $user->is_super_admin || $virtualQueue->user_id === $user->id;
    }
}
