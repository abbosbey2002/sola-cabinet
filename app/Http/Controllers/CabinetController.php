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

        // Billing sends the upcoming payment date directly as /abonent/info's
        // `charge_date` — see AbonentProfile::nextChargeDate(). The extra round
        // trip to /tariff/connected only runs for the accounts billing has not
        // sent `charge_date` for yet, deriving the date from the day the tariff
        // started instead.
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
            //
            // "Last payment" means real money received, not the latest row:
            // billing lists corrections/charges (negative amounts, shown as
            // "Списание" on /finance) in the same list. The most recent one of
            // those is not a payment the subscriber made, so it is skipped
            // rather than shown as if it were.
            'lastPayment' => collect($payments['rows'])->first(
                fn (array $row): bool => (float) ($row['amount'] ?? 0) > 0,
            ),
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
