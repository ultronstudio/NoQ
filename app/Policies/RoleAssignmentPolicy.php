<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Models\VirtualQueue;

/**
 * Who may delegate access to a VirtualQueue. A supervisor (creator,
 * explicit QueueSupervisor, or super admin) may deputize
 * ServicePointWorkers for their own queue, but only a super admin may
 * appoint another QueueSupervisor — otherwise a supervisor could freely
 * mint peers with their own full access.
 */
class RoleAssignmentPolicy
{
    public function create(User $user, VirtualQueue $virtualQueue, Role $role): bool
    {
        return match ($role) {
            Role::QueueSupervisor => $user->is_super_admin,
            Role::ServicePointWorker => $user->isSupervisorOf($virtualQueue),
        };
    }

    public function delete(User $user, RoleAssignment $roleAssignment): bool
    {
        return $this->create($user, $roleAssignment->virtualQueue, $roleAssignment->role);
    }
}
