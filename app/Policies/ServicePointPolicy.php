<?php

namespace App\Policies;

use App\Models\ServicePoint;
use App\Models\User;

/**
 * A ServicePoint has no owner of its own: authorization is derived from
 * the VirtualQueue it belongs to.
 */
class ServicePointPolicy
{
    public function view(User $user, ServicePoint $servicePoint): bool
    {
        return $servicePoint->virtualQueue->user_id === $user->id;
    }

    public function manage(User $user, ServicePoint $servicePoint): bool
    {
        return $servicePoint->virtualQueue->user_id === $user->id;
    }
}
