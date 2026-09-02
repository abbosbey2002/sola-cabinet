<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Support\AbonentProfile;
use App\Support\BillingHistory;
use App\Support\Period;
use Illuminate\Contracts\View\View;

/**
 * "Финансовая статистика" — payment history over a chosen period.
 *
 * index() renders the page; filter() re-renders only the table block, so the
 * period control updates without a page reload (spec §1.1).
 */
final class PaymentController extends Controller
{
    public function index(): View
    {
        $period = Period::currentMonth();

        return $this->view->make('payment.index', [
            'profile' => AbonentProfile::from($this->sola->abonentInfo($this->accountId()), $this->session->billingLogin()),
            'accounts' => $this->accounts(),
            'period' => $period,
            'payments' => $this->history()->payments($this->accountId(), $period),
        ]);
    }

    public function filter(PeriodRequest $request): View
    {
        $period = $request->period();

        return view('payment.result', [
            'period' => $period,
            'payments' => $this->history()->payments($this->accountId(), $period),
        ]);
    }

    private function history(): BillingHistory
    {
        return new BillingHistory($this->sola);
    }
}
