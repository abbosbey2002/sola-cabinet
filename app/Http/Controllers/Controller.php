<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Mobile_Detect;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Controller constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param $path
     * @param $compact
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view($path, $compact)
    {
        $agent = new Mobile_Detect();

        if ($agent->isMobile()) {
            $folder = 'mobile.' . $path;
        } else {
            $folder = 'desktop.' . $path;
        }

        if ($compact) {
            return view($folder, $compact);
        }

        return view($folder);
    }
}
