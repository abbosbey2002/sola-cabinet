<?php

namespace App\Http\Controllers\Site\Services;

use App\Helpers\Requests;
use App\Helpers\SetCookie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as ExController;

class Controller extends ExController
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index()
    {
        try {
            $devices = $this->requests->getDevices();
        } catch (\Exception $exception) {
            abort(403);
        }

        try {
            $info = $this->requests->abonentInfo();
        } catch (\Exception $exception) {
            abort(403);
        }

        $type = $this->cookie->getType();

        return $this->view('services.index', compact('devices', 'info', 'type'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function newDevice()
    {
        try {
            $response = $this->requests->newDevice();
        } catch (\Exception $exception) {
            abort(404);
        }

        if ($response['status'] == 200) {
            return redirect()->back()->with('info', 'success');
        }

        return redirect()->back()->withErrors($response['body']['errMsg']);
    }
}
