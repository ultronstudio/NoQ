<?php

namespace App\Policies;

use App\Models\ServicePoint;
use App\Models\User;

/**
 * A ServicePoint has no owner of its own: authorization is derived from
 * the VirtualQueue it belongs to, plus any ServicePointWorker role
 * assigned directly to this point.
 */
class ServicePointPolicy
{
    public function view(User $user, ServicePoint $servicePoint): bool
    {
        return $user->canViewQueue($servicePoint->virtualQueue);
    }

    /**
     * Supervisor-level configuration: create/rename/toggle active. A
     * service point worker never manages the point itself, only serves
     * from it (see operate()).
     */
    public function manage(User $user, ServicePoint $servicePoint): bool
    {
        return $user->isSupervisorOf($servicePoint->virtualQueue);
    }

    /**
     * Whether the user may call the next entry to / resolve entries at
     * this specific service point: supervisors for any point in their
     * queue, a service point worker only for the point(s) assigned to
     * them.
     */
    public function operate(User $user, ServicePoint $servicePoint): bool
    {
        return $user->canOperateServicePoint($servicePoint);
    }
}
