<?php

namespace App\Http\Controllers;

use App\Helpers\SetCookie;
use Carbon\Carbon;
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
                'name' => 'Январь',
                'month' => Carbon::parse('first day of January')->format('Y-m')
            ],
            2 => [
                'name' => 'Февраль',
                'month' => Carbon::parse('first day of February')->format('Y-m')
            ],
            3 => [
                'name' => 'Март',
                'month' => Carbon::parse('first day of March')->format('Y-m')
            ],
            4 => [
                'name' => 'Апрель',
                'month' => Carbon::parse('first day of April')->format('Y-m')
            ],
            5 => [
                'name' => 'Май',
                'month' => Carbon::parse('first day of May')->format('Y-m')
            ],
            6 => [
                'name' => 'Июнь',
                'month' => Carbon::parse('first day of June')->format('Y-m')
            ],
            7 => [
                'name' => 'Июля',
                'month' => Carbon::parse('first day of July')->format('Y-m')
            ],
            8 => [
                'name' => 'Август',
                'month' => Carbon::parse('first day of August')->format('Y-m')
            ],
            9 => [
                'name' => 'Сентябрь',
                'month' => Carbon::parse('first day of September')->format('Y-m')
            ],
            10 => [
                'name' => 'Октябрь',
                'month' => Carbon::parse('first day of October')->format('Y-m')
            ],
            11 => [
                'name' => 'Ноябрь',
                'month' => Carbon::parse('first day of November')->format('Y-m')
            ],
            12 => [
                'name' => 'Декабрь',
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
}
