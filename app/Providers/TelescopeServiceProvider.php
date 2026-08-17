<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Telescope, wired for a cabinet that has no users and no database of its own.
 *
 * Two things differ from the published stub:
 *
 * 1. The gate takes no User. There is no auth guard here — the session is an
 *    encrypted cookie set after an SMS check — so authorisation is decided by
 *    environment: Telescope is a local debugging tool and nothing else.
 *
 * 2. Secrets are hidden in every environment, including local. The stub skips
 *    hiding locally, which would be wrong here: every outgoing request carries
 *    the SOLA service account's Basic auth and the X-Access-Token signature,
 *    and the login flow carries a live SMS code. Those must not sit in a
 *    SQLite file on anyone's laptop.
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal): bool {
            return $isLocal
                || $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });
    }

    /**
     * The same two lists cover incoming requests and the outgoing calls to the
     * billing API — Telescope's HTTP client watcher reuses them.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestHeaders([
            // HTTP Basic for the SOLA service account: base64, not encryption.
            'authorization',
            // md5(username + secret + body) — replaying it replays the call.
            'x-access-token',
            'cookie',
            'set-cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);

        Telescope::hideRequestParameters([
            '_token',
            'password',
            'curr_password',
            'new_password',
            // The SMS code, from the verify form and from the API payload.
            'code',
            'smsCode',
        ]);

        // Account and phone numbers stay visible on purpose: they are the
        // identifiers a developer needs to follow a subscriber through a
        // trace, and they are not credentials. The database file is
        // gitignored and Telescope only runs locally.
    }

    /**
     * Who may open /telescope outside local. Nobody: there is no user model to
     * check against, and the dashboard exposes request bodies wholesale.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', fn (): bool => $this->app->environment('local'));
    }
}
