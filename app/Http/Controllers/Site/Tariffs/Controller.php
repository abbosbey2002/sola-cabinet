<?php

namespace App\Http\Controllers\Site\Tariffs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as ExController;

use App\Helpers\Requests;

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
            $tariffs = $this->requests->getTariff();
        } catch (\Exception $exception) {
            abort(403);
        }

        try {
            $info = $this->requests->abonentInfo();
        } catch (\Exception $exception) {
            abort(403);
        }

        return $this->view('tariffs.index', compact('info', 'tariffs'));
    }

    public function connect()
    {

    }
}
