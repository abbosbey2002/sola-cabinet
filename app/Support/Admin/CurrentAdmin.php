<?php

declare(strict_types=1);

namespace App\Support\Admin;

use App\Support\AdminSession;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in admin's row, read once per request.
 *
 * The admin cookie only carries an id; the role is always read fresh from the
 * database, so demoting or deleting an admin takes effect on their next click
 * rather than when their 8-hour cookie runs out.
 */
final class CurrentAdmin
{
    private bool $loaded = false;

    private ?AdminIdentity $identity = null;

    public function __construct(private readonly AdminSession $session) {}

    public function identity(): ?AdminIdentity
    {
        if ($this->loaded) {
            return $this->identity;
        }

        $this->loaded = true;

        $adminId = $this->session->adminId();

        if ($adminId === null) {
            return null;
        }

        $row = DB::table('admins')->where('id', $adminId)->first(['id', 'username', 'role']);

        if ($row === null) {
            return null;
        }

        return $this->identity = new AdminIdentity(
            (int) $row->id,
            (string) $row->username,
            // An unknown role grants nothing — fail closed.
            AdminRole::tryFrom((string) ($row->role ?? '')),
        );
    }

    /** Re-read on the next call — see ActivityRecorder::reset() for why. */
    public function forget(): void
    {
        $this->loaded = false;
        $this->identity = null;
    }

    public function can(AdminAbility $ability): bool
    {
        return $this->identity()?->role?->can($ability) ?? false;
    }
}
