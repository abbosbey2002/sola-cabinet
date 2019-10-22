<?php

namespace App\Http\Controllers;

use App\Helpers\SetCookie;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Mobile_Detect;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $cookie;

    /**
     * Controller constructor.
     * @param SetCookie $cookie
     */
    public function __construct(SetCookie $cookie)
    {
        $this->cookie = $cookie;
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

    /**
     * @return array
     */
    public function month()
    {
        return [
            1 => [
                'name' => trans('app.months.1'),
                'month' => Carbon::parse('first day of January')->format('Y-m')
            ],
            2 => [
                'name' => trans('app.months.2'),
                'month' => Carbon::parse('first day of February')->format('Y-m')
            ],
            3 => [
                'name' => trans('app.months.3'),
                'month' => Carbon::parse('first day of March')->format('Y-m')
            ],
            4 => [
                'name' => trans('app.months.4'),
                'month' => Carbon::parse('first day of April')->format('Y-m')
            ],
            5 => [
                'name' => trans('app.months.5'),
                'month' => Carbon::parse('first day of May')->format('Y-m')
            ],
            6 => [
                'name' => trans('app.months.6'),
                'month' => Carbon::parse('first day of June')->format('Y-m')
            ],
            7 => [
                'name' => trans('app.months.7'),
                'month' => Carbon::parse('first day of July')->format('Y-m')
            ],
            8 => [
                'name' => trans('app.months.8'),
                'month' => Carbon::parse('first day of August')->format('Y-m')
            ],
            9 => [
                'name' => trans('app.months.9'),
                'month' => Carbon::parse('first day of September')->format('Y-m')
            ],
            10 => [
                'name' => trans('app.months.10'),
                'month' => Carbon::parse('first day of October')->format('Y-m')
            ],
            11 => [
                'name' => trans('app.months.11'),
                'month' => Carbon::parse('first day of November')->format('Y-m')
            ],
            12 => [
                'name' => trans('app.months.12'),
                'month' => Carbon::parse('first day of December')->format('Y-m')
            ],
        ];
    }

    /**
     * @param string $lang
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeLang(string $lang)
    {
        $this->cookie->setLang($lang);
        return redirect()->back();
    }

    public function getAccounts()
    {
        try {
            $accounts = $this->requests->selectUsers();
        } catch (Exception $exception) {
            return abort(500);
        }

        return $accounts;
    }
}
