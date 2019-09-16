<?php

namespace App\Http\Controllers\Site\Traffic;

use App\Helpers\Requests;
use App\Helpers\SetCookie;
use App\Http\Requests\Traffic\Detail as DetailRequest;
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
            $detail = $this->requests->getTrafficDetail();
        } catch (Exception $exception) {
            return abort(500);
        }

        $months = $this->month();


        if($detail['status'] == 200) {
            $input = array_sum(array_map(function ($q) {
                    return $q['traffic_input'];
                }, $detail['body']['detail'])) / 1024 / 1024;

            $output = array_sum(array_map(function ($q) {
                    return $q['traffic_output'];
                }, $detail['body']['detail']))/ 1024 / 1024;

            return $this->view('trafic.index', compact('info', 'detail', 'output', 'input', 'months'));
        }

        return abort(403);
    }

    /**
     * @param DetailRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function other_month(DetailRequest $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('traffic');
        }

        try {
            $info = $this->requests->abonentInfo();
        } catch (Exception $exception) {
            return abort(500);
        }

        try {
            $detail = $this->requests->getTrafficDetailMonth($request);
        } catch (Exception $exception) {
            return abort(500);
        }

        $month = $request->getMonth();
        $months = $this->month();

        if($detail['status'] == 200) {
            $input = array_sum(array_map(function ($q) {
                    return $q['traffic_input'];
                }, $detail['body']['detail'])) / 1024 / 1024;

            $output = array_sum(array_map(function ($q) {
                    return $q['traffic_output'];
                }, $detail['body']['detail']))/ 1024 / 1024;

            return $this->view('trafic.month', compact('info', 'detail', 'output', 'input', 'months', 'month'));
        }

        return abort(403);
    }
}
