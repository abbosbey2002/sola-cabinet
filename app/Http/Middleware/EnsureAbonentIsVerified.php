<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AbonentSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the cabinet: only a subscriber who passed the SMS check gets in.
 */
final class EnsureAbonentIsVerified
{
    public function __construct(private readonly AbonentSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->session->isVerified()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
