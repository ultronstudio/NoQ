<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Grants or revokes the super admin flag. There is no self-service UI for
 * this: it is the highest privilege level in the app (see App\Models\User
 * and docs/domain-model.md), so it is bootstrapped from the command line
 * by whoever already has server access.
 */
class ManageSuperAdmin extends Command
{
    /**
     * @var string
     */
    protected $signature = 'noq:super-admin {email} {--revoke : Remove super admin instead of granting it}';

    /**
     * @var string
     */
    protected $description = 'Grant or revoke the super admin flag for a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("Žádný uživatel s e-mailem [{$email}] neexistuje.");

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');

        // Set directly rather than update(): is_super_admin is deliberately
        // excluded from $fillable so it can never be mass-assigned from a
        // request elsewhere in the app.
        $user->is_super_admin = ! $revoke;
        $user->save();

        $this->info($revoke
            ? "Uživateli {$user->name} ({$user->email}) byla odebrána role super admina."
            : "Uživatel {$user->name} ({$user->email}) je nyní super admin.");

        return self::SUCCESS;
    }
}
