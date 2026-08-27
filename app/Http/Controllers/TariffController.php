<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TariffConnectRequest;
use App\Support\AbonentProfile;
use App\Support\ConnectedTariff;
use App\Support\TariffVisibility;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * "Тариф" — the current plan, and the list of plans it can be swapped for.
 *
 * This used to be a block on the dashboard. It has a page of its own now: the
 * dashboard was one long screen of everything and a subscriber looking for
 * their tariff had to scroll past their balance, their devices and their
 * payments to find it.
 */
final class TariffController extends Controller
{
    public function index(TariffVisibility $visibility): View
    {
        $accountId = $this->accountId();

        $profile = AbonentProfile::from($this->sola->abonentInfo($accountId));

        // A legal entity (yuridik shaxs) is not offered this page at all —
        // the nav link and dashboard card are already hidden for them, this
        // is the control the hiding is a courtesy for.
        abort_if($profile->isLegalEntity(), 403);

        // A third round trip, for one field: /abonent/info reports the current
        // tariff without the date it started, and this is the only endpoint
        // that carries it. The page is read-only and already makes two calls,
        // so the cost is a page that loads ~200 ms slower rather than a
        // subscriber who cannot see when their plan began.
        $connected = ConnectedTariff::current(
            $this->sola->connectedTariffs($accountId),
            $profile->currentTariffId(),
        );

        $tariffs = $visibility->filter((array) $this->sola->availableTariffs($accountId)->get('tariffs', []));

        return $this->view->make('cabinet.tariff', [
            'profile' => $profile,
            'accounts' => $this->accounts(),
            'tariffs' => $tariffs,
            'startedAt' => $connected?->startedAt(),
            'nextPeriodStart' => $this->nextPeriodStart(),
            // Same source and fallback as the home page's "next charge" —
            // never invented: null when billing has not told us a date, and
            // the timing modal simply omits the line rather than guess one.
            'nextCharge' => $profile->nextChargeDate() ?? $connected?->nextChargeDate(),
        ]);
    }

    /**
     * Connect a tariff, either immediately or from the 1st of next month.
     *
     * POST, not GET: this charges the subscriber. The previous version was a
     * link, so the request carried no CSRF token and any third-party page could
     * fire it from inside an authenticated session.
     *
     * Everything the page decides in Blade is decided again here. The view's
     * conditions are a courtesy to the subscriber, not a control: the form is
     * reachable with a valid CSRF token by anyone signed in, whatever their
     * account type and whatever tariff id they put in the body.
     */
    public function connect(TariffConnectRequest $request, TariffVisibility $visibility): RedirectResponse
    {
        $accountId = $this->accountId();
        $isPermanent = $this->session->isPermanent();

        // The same rule cabinet/tariff.blade.php gates the form on: only a
        // permanent subscriber may switch, unless there is no tariff at all yet.
        $profile = AbonentProfile::from($this->sola->abonentInfo($accountId));

        abort_if($profile->isLegalEntity(), 403);

        abort_unless($isPermanent || blank($profile->currentTariff()), 403);

        // The tariff has to be one billing actually offers THIS account, and
        // one the admin has opted in to /tariffs — the same filter that page
        // renders through, so an id nobody enabled can never be connected by
        // posting it directly.
        $available = $visibility->filter((array) $this->sola->availableTariffs($accountId)->get('tariffs', []));

        $offered = collect($available)
            ->contains(fn (array $tariff): bool => (int) $tariff['tariff_id'] === $request->tariffId());

        abort_unless($offered, 403);

        // Only a permanent subscriber is offered the timing dialog; for anyone
        // else the page posts "now", so that is what the server honours rather
        // than trusting a hand-edited body.
        $timing = $isPermanent ? $request->timing() : 'now';

        $date = match ($timing) {
            'now' => CarbonImmutable::now(),
            default => $this->nextPeriodStart(),
        };

        $response = $this->sola->connectTariff($accountId, $request->tariffId(), $date->format('Y-m-d'));

        if ($response->failed()) {
            $this->flashDanger($response->errorMessage() ?? trans('errors.unknown'));
        } else {
            $this->flashInfo(trans('app.modal.success_tariff'));
        }

        return redirect()->route('tariff');
    }

    /**
     * The 1st of next month — both when a deferred tariff switch actually
     * takes effect (connect()) and what the timing modal shows the
     * subscriber before they choose (index()), so the two never drift apart
     * within one request. Each call reads the current wall-clock time
     * independently, so a session that stays open across the month boundary
     * (page loaded 23:59 on the last day, submitted after midnight) could in
     * principle see a date the modal already showed roll over by one month —
     * accepted as a rare, low-stakes edge case rather than plumbing a shared
     * timestamp through the request lifecycle for it.
     */
    private function nextPeriodStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->addMonth()->firstOfMonth();
    }
}
