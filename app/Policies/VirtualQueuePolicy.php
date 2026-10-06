<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VirtualQueue;

/**
 * Ownership-based authorization: a user may manage only the VirtualQueue
 * records they created. There is no role/permission system (see
 * docs/domain-model.md).
 */
class VirtualQueuePolicy
{
    public function view(User $user, VirtualQueue $virtualQueue): bool
    {
        return $virtualQueue->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VirtualQueue $virtualQueue): bool
    {
        return $virtualQueue->user_id === $user->id;
    }

    public function delete(User $user, VirtualQueue $virtualQueue): bool
    {
        return $virtualQueue->user_id === $user->id;
    }
}
