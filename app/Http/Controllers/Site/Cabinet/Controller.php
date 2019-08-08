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

    /**
     * Controller constructor.
     * @param Requests $requests
     */
    public function __construct(Requests $requests)
    {
        $this->requests = $requests;
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

        return view('site.cabinet.index', compact('info'));
    }

}
