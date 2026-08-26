<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Support\AbonentProfile;
use App\Support\BillingHistory;
use App\Support\Period;
use Illuminate\Contracts\View\View;

/**
 * "Статистика" — traffic over a chosen period.
 *
 * index() renders the page; filter() re-renders only the result block, so the
 * period control updates without a page reload (spec §1.1).
 */
final class TrafficController extends Controller
{
    public function index(): View
    {
        $period = Period::currentMonth();

        return $this->view->make('trafic.index', [
            'profile' => AbonentProfile::from($this->sola->abonentInfo($this->accountId())),
            'accounts' => $this->accounts(),
            'period' => $period,
            'traffic' => $this->history()->traffic($this->accountId(), $period),
        ]);
    }

    public function filter(PeriodRequest $request): View
    {
        $period = $request->period();

        return view('trafic.result', [
            'period' => $period,
            'traffic' => $this->history()->traffic($this->accountId(), $period),
        ]);
    }

    private function history(): BillingHistory
    {
        return new BillingHistory($this->sola);
    }
}
