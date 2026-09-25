<?php

use App\Exceptions\SolaUnavailableException;
use App\Http\Middleware\AuditAdminAccess;
use App\Http\Middleware\EnsureAbonentIsVerified;
use App\Http\Middleware\EnsureAdminCan;
use App\Http\Middleware\EnsureAdminIsAuthenticated;
use App\Http\Middleware\RecordActivity;
use App\Http\Middleware\RedirectIfAbonentIsVerified;
use App\Http\Middleware\RedirectIfAdminIsAuthenticated;
use App\Http\Middleware\SetLocale;
use App\Support\Activity\RequestContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
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
        // RecordActivity comes after it so a recorded event carries the
        // locale the page was actually rendered in.
        $middleware->web(append: [
            SetLocale::class,
            RecordActivity::class,
        ]);

        // Laravel sorts ThrottleRequests ahead of the web group's own
        // middleware, which would put a throttled login's 429 outside
        // RecordActivity and out of the journal. Pinned in front of it.
        $middleware->prependToPriorityList(
            before: ThrottleRequests::class,
            prepend: RecordActivity::class,
        );

        // Written by activity.js in the browser (screen size, theme) and read
        // back as plain text; it carries nothing secret and RequestContext
        // re-validates every part of it.
        $middleware->encryptCookies(except: [
            RequestContext::SCREEN_COOKIE,
        ]);

        $middleware->alias([
            'abonent.verified' => EnsureAbonentIsVerified::class,
            'abonent.guest' => RedirectIfAbonentIsVerified::class,
            'admin.auth' => EnsureAdminIsAuthenticated::class,
            'admin.guest' => RedirectIfAdminIsAuthenticated::class,
            'admin.can' => EnsureAdminCan::class,
            'admin.audit' => AuditAdminAccess::class,
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
