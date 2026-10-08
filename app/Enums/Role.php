<?php

namespace App\Enums;

/**
 * Scoped, delegated access an operator can hold on top of two implicit
 * levels that are not represented here: being a VirtualQueue's creator
 * (virtual_queues.user_id) and being a super admin (users.is_super_admin).
 * Both already grant full access and are checked directly in
 * App\Models\User, not via a RoleAssignment row.
 */
enum Role: string
{
    case QueueSupervisor = 'queue_supervisor';
    case ServicePointWorker = 'service_point_worker';

    public function label(): string
    {
        return match ($this) {
            self::QueueSupervisor => 'Vedoucí operátor',
            self::ServicePointWorker => 'Pracovník přepážky',
        };
    }
}
