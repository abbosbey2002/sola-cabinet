<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AbonentSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale the visitor picked, which lives in the "lang" cookie.
 *
 * The value is checked against the supported list: the locale ends up in the
 * translation file path, so an unvalidated one is a path-traversal primitive.
 */
final class SetLocale
{
    public function __construct(private readonly AbonentSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->session->locale();

        if ($locale !== null && in_array($locale, (array) config('app.supported_locales'), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
