<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TopUpRequest;
use App\Support\AbonentProfile;
use App\Support\IwonCheckout;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * "Hisobni to'ldirish" — top up the balance via iWon's hosted Uzcard/Humo form.
 *
 * iWon's own integration has no callback and no signature (docs/api/
 * iwon-api.md): the redirect just leaves the app, and whatever comes back on
 * returnUrl is the subscriber's browser, not a confirmation. The only source
 * of truth is billing's own balance, asked for the same way Payme/Click
 * top-ups already confirm everywhere else in this app.
 *
 * With no database, "what was I waiting for" has to survive the round trip
 * some other way: a short-lived cookie carries the balance and amount at the
 * moment of redirect, read back in checkReturn(). It is scoped to this one
 * payment attempt — not identity state, so it does not belong in
 * AbonentSession and is not cleared by logout.
 */
final class TopUpController extends Controller
{
    private const string COOKIE_NAME = 'pending_topup';

    private const int COOKIE_MINUTES = 30;

    /**
     * How long checkReturn() keeps auto-refreshing before it gives up and
     * points the subscriber at their payment history instead. Billing has
     * shown a top-up within a few seconds in every observed case (same
     * mechanism as Payme/Click); this is slack for a slow response, not an
     * expected wait.
     */
    private const int CHECK_TIMEOUT_MINUTES = 5;

    public function index(): View
    {
        abort_unless((bool) config('iwon.active'), 404);

        return $this->view->make('cabinet.topup', [
            'profile' => AbonentProfile::from($this->sola->abonentInfo($this->accountId())),
            'accounts' => $this->accounts(),
        ]);
    }

    public function store(TopUpRequest $request): RedirectResponse
    {
        abort_unless((bool) config('iwon.active'), 404);

        $accountId = $this->accountId();
        $amount = $request->amount();

        $balanceBefore = AbonentProfile::from($this->sola->abonentInfo($accountId))->balance();

        $redirect = IwonCheckout::fromConfig()->redirectUrl($amount, $accountId, route('topup.return'));

        // With no callback from iWon and no database, this line — plus the
        // matching one in checkReturn() — is the only record support can
        // ever produce for "I paid but wasn't credited". additional_id is
        // the one thread connecting it to iWon's own back office.
        Log::info('iwon.topup.initiated', [
            'account_id' => $accountId,
            'amount_som' => $amount,
            'balance_before' => $balanceBefore,
            'additional_id' => $redirect->additionalId,
        ]);

        Cookie::queue(self::COOKIE_NAME, (string) json_encode([
            'account_id' => $accountId,
            'balance_before' => $balanceBefore,
            'amount' => $amount,
            'initiated_at' => CarbonImmutable::now()->toIso8601String(),
            'additional_id' => $redirect->additionalId,
        ]), self::COOKIE_MINUTES);

        return redirect()->away($redirect->url);
    }

    /**
     * Where iWon's returnUrl lands the subscriber. Never trusted as proof of
     * payment on its own — see the class docblock.
     */
    public function checkReturn(): View|RedirectResponse
    {
        abort_unless((bool) config('iwon.active'), 404);

        $pending = $this->pending();

        if ($pending === null) {
            // Reached with no attempt on record — a stale bookmark, a second
            // tab, or the 30-minute cookie already expired. Nothing to check
            // against; send them to start a fresh one rather than guess.
            $this->flashInfo(trans('app.topup.no_pending'));

            return redirect()->route('topup');
        }

        if ($pending['account_id'] !== $this->accountId()) {
            // The account switcher (set.account) let the subscriber change
            // which account is "current" mid-flow — comparing this account's
            // fresh balance against a snapshot taken for a DIFFERENT account
            // would be meaningless, and could show a false success/checking
            // state. Same treatment as no cookie at all: start over.
            $this->flashInfo(trans('app.topup.no_pending'));

            return redirect()->route('topup');
        }

        $profile = AbonentProfile::from($this->sola->abonentInfo($this->accountId()));
        $balanceNow = $profile->balance();

        // A 1 so'm epsilon: billing balances have carried kopeck fractions
        // (docs/api/SOLA_API.md), and an exact float equality check would
        // miss a genuine credit by a rounding hair.
        $credited = $balanceNow >= $pending['balance_before'] + $pending['amount'] - 1.0;

        if ($credited) {
            Cookie::queue(Cookie::forget(self::COOKIE_NAME));
        }

        $waitedMinutes = CarbonImmutable::parse($pending['initiated_at'])->diffInMinutes(CarbonImmutable::now());
        $timedOut = ! $credited && $waitedMinutes >= self::CHECK_TIMEOUT_MINUTES;

        // The matching half of the initiated log above — whichever of these
        // fires (or neither, on a plain refresh mid-wait) is the closest
        // thing this integration has to a payment record.
        if ($credited || $timedOut) {
            Log::info('iwon.topup.checked', [
                'account_id' => $this->accountId(),
                'additional_id' => $pending['additional_id'],
                'credited' => $credited,
                'balance_before' => $pending['balance_before'],
                'balance_now' => $balanceNow,
                'waited_minutes' => $waitedMinutes,
            ]);
        }

        return $this->view->make('cabinet.topup-return', [
            'profile' => $profile,
            'accounts' => $this->accounts(),
            'credited' => $credited,
            'amount' => $pending['amount'],
            'balanceNow' => $balanceNow,
            'timedOut' => $timedOut,
        ]);
    }

    /**
     * @return array{account_id: string, balance_before: float, amount: float, initiated_at: string, additional_id: string}|null
     */
    private function pending(): ?array
    {
        $raw = request()->cookie(self::COOKIE_NAME);

        if (! is_string($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (
            ! is_array($decoded)
            || ! is_string($decoded['account_id'] ?? null)
            || $decoded['account_id'] === ''
            || ! is_numeric($decoded['balance_before'] ?? null)
            || ! is_numeric($decoded['amount'] ?? null)
            || ! is_string($decoded['initiated_at'] ?? null)
        ) {
            return null;
        }

        return [
            'account_id' => $decoded['account_id'],
            'balance_before' => (float) $decoded['balance_before'],
            'amount' => (float) $decoded['amount'],
            // Only used for the log line above — falls back rather than
            // rejecting the whole cookie, so a top-up started in the few
            // minutes before this field was added still resolves correctly.
            'additional_id' => is_string($decoded['additional_id'] ?? null) ? $decoded['additional_id'] : 'unknown',
            'initiated_at' => $decoded['initiated_at'],
        ];
    }
}
