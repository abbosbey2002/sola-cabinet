<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Admin\CurrentAdmin;
use App\Support\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the admin panel: only a logged-in admin gets in.
 *
 * The cookie alone is not enough: the admin it names must still exist. A
 * deleted admin's cookie is valid for up to eight hours and now carries a
 * role — it must stop working on the next request, not at expiry.
 */
final class EnsureAdminIsAuthenticated
{
    public function __construct(
        private readonly AdminSession $session,
        private readonly CurrentAdmin $admin,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->admin->forget();

        if (! $this->session->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        if ($this->admin->identity() === null) {
            $this->session->logout();

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
