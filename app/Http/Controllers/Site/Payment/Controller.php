<?php

namespace App\Http\Controllers\Site\Payment;

use App\Helpers\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as ExController;

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

        return $this->view('payment.index', compact('payments', 'info'));
    }
}
