<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\AbonentProfile;
use App\Support\BillingHistory;
use App\Support\ChargeCycle;
use App\Support\ConnectedTariff;
use App\Support\Period;
use Illuminate\Contracts\View\View;

/**
 * The landing page, and the services page beside it.
 *
 * The dashboard used to be everything on one screen — balance, tariff,
 * devices, payments and the entry-point cards stacked into a single scroll.
 * It answers one question now, "will my balance last?", and hands the other
 * three to their own pages through three links.
 */
final class CabinetController extends Controller
{
    public function index(): View
    {
        $accountId = $this->accountId();

        $profile = AbonentProfile::from($this->sola->abonentInfo($accountId));

        // Billing sends no charge date of its own, so it is derived: the client's
        // rule is that a tariff's period ends on the day of the month it started,
        // and /tariff/connected is the only endpoint carrying that day. One extra
        // round trip, for the number this page is built around. Should billing
        // ever send the date itself, that wins — it would know about anything
        // this arithmetic cannot see.
        $charge = $profile->nextChargeDate() ?? ConnectedTariff::current(
            $this->sola->connectedTariffs($accountId),
            $profile->currentTariffId(),
        )?->nextChargeDate();

        // The last payment card. The current month is one API round trip; a
        // wider window would be several, and this page already makes four.
        // When the month is empty the card says so rather than reaching back
        // for an older payment the subscriber did not ask about.
        $payments = (new BillingHistory($this->sola))
            ->payments($accountId, Period::currentMonth());

        return $this->view->make('cabinet.index', [
            'profile' => $profile,
            'accounts' => $this->accounts(),
            // No charge date from billing means no meter: thirty-one ticks
            // drawn against a guessed date would be a confident lie.
            'cycle' => $charge !== null ? ChargeCycle::endingAt($charge) : null,
            // The device counts on this page come from /abonent/info, which is
            // already loaded — /device/list is a round trip this page does not
            // need. The permit list lives on /devices.
            'lastPayment' => $payments['rows'][0] ?? null,
        ]);
    }

    /**
     * "Xizmatlar" — promotions, loyalty, support. Every card here is an entry
     * point to somewhere else, so the page carries no billing data of its own.
     */
    public function services(): View
    {
        return $this->view->make('cabinet.services', [
            'profile' => AbonentProfile::from($this->sola->abonentInfo($this->accountId())),
            'accounts' => $this->accounts(),
        ]);
    }
}
