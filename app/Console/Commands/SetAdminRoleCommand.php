<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Admin\AdminRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Change an existing admin's role. Takes effect on their next request — the
 * role is read from the database each time, not from their cookie.
 */
final class SetAdminRoleCommand extends Command
{
    protected $signature = 'admin:role {username} {role : admin, analyst or sales}';

    protected $description = 'Change an admin panel account\'s role';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $role = AdminRole::tryFrom((string) $this->argument('role'));

        if ($role === null) {
            $this->error('Unknown role. Use one of: '.implode(', ', AdminRole::values()).'.');

            return self::FAILURE;
        }

        $updated = DB::table('admins')->where('username', $username)->update([
            'role' => $role->value,
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            $this->error("Admin \"{$username}\" does not exist.");

            return self::FAILURE;
        }

        $this->info("Admin \"{$username}\" is now {$role->value}.");

        return self::SUCCESS;
    }
}
