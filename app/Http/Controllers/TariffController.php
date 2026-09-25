<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TariffConnectRequest;
use App\Support\AbonentProfile;
use App\Support\Activity\DeniedReason;
use App\Support\Activity\Outcome;
use App\Support\Activity\TariffChangeRecorder;
use App\Support\ConnectedTariff;
use App\Support\ErrorMessages;
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

        $profile = AbonentProfile::from($this->sola->abonentInfo($accountId), $this->session->billingLogin());

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
            'nextPeriodStart' => $this->connectionDate($profile, $connected),
        ]);
    }

    /**
     * Connect a tariff, either immediately or from the next billing cycle.
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
    public function connect(
        TariffConnectRequest $request,
        TariffVisibility $visibility,
        TariffChangeRecorder $changes,
    ): RedirectResponse {
        $accountId = $this->accountId();
        $isPermanent = $this->session->isPermanent();
        $tariffId = $request->tariffId();

        // The same rule cabinet/tariff.blade.php gates the form on: only a
        // permanent subscriber may switch, unless there is no tariff at all yet.
        $info = $this->sola->abonentInfo($accountId);
        $profile = AbonentProfile::from($info);
        $this->activity()->withProfile($profile);

        // Every refusal below is recorded before the 403, so the admin's
        // tariff-change history shows who tried and why they were turned away.
        $deny = function (DeniedReason $reason, ?array $tariff = null) use ($changes, $request, $info, $tariffId): never {
            $changes->deny($request, $info, $tariffId, $tariff, $reason);
            $this->activity()->annotate(Outcome::Denied, meta: ['tariff_id' => $tariffId, 'denied_reason' => $reason->value]);

            abort(403);
        };

        if ($profile->isLegalEntity()) {
            $deny(DeniedReason::LegalEntity);
        }

        if (! $isPermanent && ! blank($profile->currentTariff())) {
            $deny(DeniedReason::NotPermanent);
        }

        // The tariff has to be one billing actually offers THIS account, and
        // one the admin has opted in to /tariffs — the same filter that page
        // renders through, so an id nobody enabled can never be connected by
        // posting it directly.
        $offeredByBilling = (array) $this->sola->availableTariffs($accountId)->get('tariffs', []);
        $tariff = self::findTariff($offeredByBilling, $tariffId);

        if ($tariff === null) {
            $deny(DeniedReason::NotInAvailable);
        }

        if (self::findTariff($visibility->filter($offeredByBilling), $tariffId) === null) {
            $deny(DeniedReason::NotAllowed, $tariff);
        }

        // Only a permanent subscriber is offered the timing dialog; for anyone
        // else the page posts "now", so that is what the server honours rather
        // than trusting a hand-edited body.
        $timing = $isPermanent ? $request->timing() : 'now';

        $connected = $timing === 'now'
            ? null
            : ConnectedTariff::current($this->sola->connectedTariffs($accountId), $profile->currentTariffId());

        $date = match ($timing) {
            'now' => CarbonImmutable::now(),
            default => $this->connectionDate($profile, $connected),
        };

        $changeId = $changes->begin($request, $info, $tariffId, $tariff, $timing, $date, $connected);

        $response = $changes->attempt(
            $changeId,
            $accountId,
            fn () => $this->sola->connectTariff($accountId, $tariffId, $date->format('Y-m-d')),
        );

        $this->activity()->annotate(Outcome::ofBilling($response), $response, [
            'tariff_id' => $tariffId,
            'old_tariff_id' => $profile->currentTariffId(),
            'timing' => $timing,
            'tariff_change_id' => $changeId,
        ]);

        if ($response->failed()) {
            $this->flashDanger(ErrorMessages::forResponse($response));
        } else {
            $this->flashInfo(trans('app.modal.success_tariff'));
        }

        return redirect()->route('tariff');
    }

    /**
     * @param  array<int, mixed>  $tariffs  /tariff/available rows
     * @return array<string, mixed>|null
     */
    private static function findTariff(array $tariffs, int $tariffId): ?array
    {
        $match = collect($tariffs)->first(
            fn (mixed $tariff): bool => is_array($tariff) && (int) ($tariff['tariff_id'] ?? 0) === $tariffId,
        );

        return is_array($match) ? $match : null;
    }

    /**
     * When a deferred tariff switch takes effect — both what connect() charges
     * against and what the timing modal shows the subscriber before they
     * choose (index()), so the two never drift apart within one request.
     *
     * The subscriber's already-known next charge date — same source and
     * fallback as the home page's "next charge" (AbonentProfile first, then
     * the connected tariff's anchor-day cycle) — is preferred over a naive
     * "1st of next month", since it is the real boundary billing will next
     * charge on, not an arbitrary calendar date. Only when billing has told
     * us neither does this fall back to the 1st of next month.
     *
     * AbonentProfile::nextChargeDate() is read straight from billing's
     * `charge_date`, with no guarantee it has already rolled forward past
     * today — unlike ConnectedTariff::nextChargeDate(), which walks forward
     * until it is. Floored at today so a stale past date from billing can
     * never send an already-elapsed date to connectTariff().
     */
    private function connectionDate(AbonentProfile $profile, ?ConnectedTariff $connected): CarbonImmutable
    {
        $date = $profile->nextChargeDate() ?? $connected?->nextChargeDate() ?? $this->nextPeriodStart();

        return $date->max(CarbonImmutable::now()->startOfDay());
    }

    /**
     * The naive fallback when billing has not told us a real next charge
     * date at all. Each call reads the current wall-clock time independently,
     * so a session that stays open across the month boundary (page loaded
     * 23:59 on the last day, submitted after midnight) could in principle see
     * a date the modal already showed roll over by one month — accepted as a
     * rare, low-stakes edge case rather than plumbing a shared timestamp
     * through the request lifecycle for it.
     */
    private function nextPeriodStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->addMonth()->firstOfMonth();
    }
}
