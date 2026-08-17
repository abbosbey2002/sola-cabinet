<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AbonentSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an already verified subscriber out of the login screens.
 */
final class RedirectIfAbonentIsVerified
{
    public function __construct(private readonly AbonentSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->session->isVerified()) {
            return redirect()->route('cabinet');
        }

        return $next($request);
    }
}
