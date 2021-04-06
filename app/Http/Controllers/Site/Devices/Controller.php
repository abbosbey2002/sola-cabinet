<?php

namespace App\Http\Controllers\Site\Devices;

use App\Helpers\Requests;
use App\Helpers\SetCookie;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{

    protected $requests;
    protected $cookie;

    public function __construct(Requests $requests, SetCookie $cookie)
    {
        $this->requests = $requests;
        $this->cookie = $cookie;
    }

    public function add()
    {
        try {
            $add = $this->requests->addDevice();
        } catch (\Exception $exception) {
            return abort(500);
        }


        if ($add['status'] == 200) {
            request()->flash('info', trans('app.header.success_device'));
        } else {
            request()->flash('danger', $add['body']['errMsg']);
        }

        return redirect()->route('cabinet');
    }
}
