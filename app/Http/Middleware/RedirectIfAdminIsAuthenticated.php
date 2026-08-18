<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an already logged-in admin out of the login screen.
 */
final class RedirectIfAdminIsAuthenticated
{
    public function __construct(private readonly AdminSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->session->isAuthenticated()) {
            return redirect()->route('admin.tariffs');
        }

        return $next($request);
    }
}
