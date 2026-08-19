<?php

use App\Exceptions\SolaUnavailableException;
use App\Http\Middleware\EnsureAbonentIsVerified;
use App\Http\Middleware\EnsureAdminIsAuthenticated;
use App\Http\Middleware\RedirectIfAbonentIsVerified;
use App\Http\Middleware\RedirectIfAdminIsAuthenticated;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: [
            'curr_password',
            'new_password',
        ]);

        // lk.sola.uz's TLS terminates at SOLA's internal gateway, which
        // reverse-proxies plain HTTP to this box -- nginx here has no
        // certificate of its own. Without this, X-Forwarded-Proto is
        // ignored and every generated URL (redirects, asset links) comes
        // back as http://, which the browser then has to upgrade itself.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // The locale lives in a cookie, so this has to run after cookie decryption.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'abonent.verified' => EnsureAbonentIsVerified::class,
            'abonent.guest' => RedirectIfAbonentIsVerified::class,
            'admin.auth' => EnsureAdminIsAuthenticated::class,
            'admin.guest' => RedirectIfAdminIsAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An upstream outage is an operational event, not a bug: log one line
        // with the endpoint instead of a stack trace on every page view.
        $exceptions->dontReport(SolaUnavailableException::class);

        // The cabinet is a thin client over the SOLA API: when that API is
        // unreachable there is nothing to render, so fail fast with a 503
        // instead of repeating a try/catch in every controller action.
        $exceptions->render(function (SolaUnavailableException $e) {
            Log::error('SOLA API unreachable', [
                'endpoint' => $e->endpoint,
                'reason' => $e->getMessage(),
            ]);

            abort(503, __('errors.service_unavailable'));
        });
    })->create();
