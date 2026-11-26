<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gates the global user directory (/users). Granting or revoking the
 * super admin flag itself has no UI on purpose (see
 * App\Console\Commands\ManageSuperAdmin), so this only covers who may
 * look up the full list of accounts and their role assignments.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin;
    }
}
