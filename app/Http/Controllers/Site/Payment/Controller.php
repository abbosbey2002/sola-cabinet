<?php

namespace App\Http\Controllers\Site\Payment;

use App\Helpers\Requests;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as ExController;

use App\Http\Requests\Payment\History as HistoryRequest;

class Controller extends ExController
{
    protected $requests;

    /**
     * Controller constructor.
     * @param Requests $requests
     */
    public function __construct(Requests $requests)
    {
        $this->requests = $requests;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index()
    {

        try {
            $payments = $this->requests->getPayments();
        } catch (\Exception $exception) {
            abort(403);
        }

        try {
            $info = $this->requests->abonentInfo();
        } catch (\Exception $exception) {
            abort(403);
        }
        $months = $this->month();

        //return $months[Carbon::now()->format('n')];
        return $this->view('payment.index', compact('payments', 'info', 'months'));
    }

    public function other_month(HistoryRequest $request)
    {
        try {
            $payments = $this->requests->getPaymentsMonth($request);
        } catch (\Exception $exception) {
            abort(403);
        }

        try {
            $info = $this->requests->abonentInfo();
        } catch (\Exception $exception) {
            abort(403);
        }

        $month = $request->getPayMonth();
        $months = $this->month();

        return $this->view('payment.month', compact('payments', 'info', 'month', 'months'));
    }


}
