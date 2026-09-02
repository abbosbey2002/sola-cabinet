<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\AbonentProfile;
use App\Support\BillingHistory;
use App\Support\ChargeCycle;
use App\Support\ConnectedDevices;
use App\Support\ConnectedTariff;
use App\Support\DashboardView;
use App\Support\IpLocation;
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
    public function index(IpLocation $geo): View
    {
        $accountId = $this->accountId();

        $profile = AbonentProfile::from($this->sola->abonentInfo($accountId), $this->session->billingLogin());

        // Billing sends the upcoming payment date directly as /abonent/info's
        // `charge_date` — see AbonentProfile::nextChargeDate(). The extra round
        // trip to /tariff/connected only runs for the accounts billing has not
        // sent `charge_date` for yet, deriving the date from the day the tariff
        // started instead.
        $charge = $profile->nextChargeDate() ?? ConnectedTariff::current(
            $this->sola->connectedTariffs($accountId),
            $profile->currentTariffId(),
        )?->nextChargeDate();

        // The last payment card looks back one year — one API round trip with
        // pay_begin/pay_end — before it admits there were none in that window.
        $payments = (new BillingHistory($this->sola))
            ->payments($accountId, Period::lastYear());

        // The dashboard shows the device count only, not an active/offline
        // breakdown (dropped at the user's request, 2026-08-28) — but it
        // still has to match /devices exactly, so it is still counted from
        // /device/list rather than /abonent/info's device_count, which is
        // billing's own separate counter.
        $devices = (array) $this->sola->devices($accountId)->get('devices', []);

        $chargeCycle = $charge !== null ? ChargeCycle::endingAt($charge) : null;

        return $this->view->make('cabinet.index', [
            'profile' => $profile,
            'accounts' => $this->accounts(),
            'dash' => DashboardView::make(
                $profile,
                $chargeCycle,
                $this->session->isOneTime(),
            ),
            // No charge date from billing means no "next charge" block: a
            // guessed date would be a confident lie.
            'cycle' => $chargeCycle,
            'totalDevices' => count($devices),
            'deviceMetricHint' => ConnectedDevices::metricHint($devices, $geo),
            'billingLogin' => $this->session->billingLogin(),
            'lastPayment' => BillingHistory::lastRealPayment($payments['rows']),
        ]);
    }

    /**
     * "Xizmatlar" — promotions, loyalty, support. Every card here is an entry
     * point to somewhere else, so the page carries no billing data of its own.
     */
    public function services(): View
    {
        return $this->view->make('cabinet.services', [
            'profile' => AbonentProfile::from($this->sola->abonentInfo($this->accountId()), $this->session->billingLogin()),
            'accounts' => $this->accounts(),
        ]);
    }
}
