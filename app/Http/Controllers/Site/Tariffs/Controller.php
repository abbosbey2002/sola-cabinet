<?php

namespace App\Http\Controllers\Site\Tariffs;

use Carbon\Carbon;
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

       // return $tariffs;

        return $this->view('tariffs.index', compact('info', 'tariffs'));
    }

    /**
     * @param int $id
     * @param string $type
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function connect(int $id, string $type)
    {
        switch ($type) {
            case 'now':
                $date = Carbon::now()->format('Y-m-d');
                break;
            case 'month':
                $date = Carbon::parse(Carbon::now()->addMonth())->firstOfMonth()->format('Y-m-d');
        }

        try {
            $response = $this->requests->setTariff(6, $date);
        } catch (\Exception $exception) {
           abort(403);
        }

        if ($response['status'] != 200) {
            return redirect()->back()->withErrors($response['body']['errMsg']);
        }

        return redirect()->back()->with('info', 'success');
    }
}
