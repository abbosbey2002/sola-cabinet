<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Admin\AdminAbility;
use App\Support\Admin\CurrentAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `admin.can:<ability>` — the signed-in admin's role must grant the ability
 * (AdminAbility), or 403. Runs after admin.auth, so an admin is known.
 */
final class EnsureAdminCan
{
    public function __construct(private readonly CurrentAdmin $admin) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless($this->admin->can(AdminAbility::from($ability)), 403);

        return $next($request);
    }
}
