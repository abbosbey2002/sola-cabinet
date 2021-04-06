<?php

namespace App\Http\Controllers\Site\Cabinet;

use Illuminate\Http\Request as Req;
use App\Http\Controllers\Controller as BaseController;

use App\Helpers\SetCookie;
use App\Helpers\Requests;

use \Exception;

class Controller extends BaseController
{
    protected $requests;
    protected $cookie;

    /**
     * Controller constructor.
     * @param Requests $requests
     * @param SetCookie $cookie
     */
    public function __construct(Requests $requests, SetCookie $cookie)
    {
        $this->requests = $requests;
        $this->cookie = $cookie;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index()
    {
        try {
            $info = $this->requests->abonentInfo();
        } catch (Exception $exception) {
            return abort(500);
        }

        try {
            $devices = $this->requests->getDevices();
        } catch (Exception $exception) {
            return abort(500);
        }

        $accounts = $this->getAccounts();


        return $this->view('cabinet.index', compact('info', 'accounts'));
    }

}
