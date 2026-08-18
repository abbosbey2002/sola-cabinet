<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the admin panel: only a logged-in admin gets in.
 */
final class EnsureAdminIsAuthenticated
{
    public function __construct(private readonly AdminSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->session->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
