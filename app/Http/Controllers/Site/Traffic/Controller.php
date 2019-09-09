<?php

namespace App\Http\Controllers\Site\Traffic;

use App\Helpers\Requests;
use App\Helpers\SetCookie;
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
        return $this->requests->getTrafficDetail();
    }
}
