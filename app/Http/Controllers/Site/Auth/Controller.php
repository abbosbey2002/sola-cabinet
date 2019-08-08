<?php

namespace App\Http\Controllers\Site\Auth;

use Illuminate\Http\Request as Req;
use App\Http\Controllers\Controller as BaseController;

use App\Http\Requests\Auth\Login as LoginRequest;
use App\Http\Requests\Auth\Verify as VerifyRequest;

use App\Helpers\Requests;
use App\Helpers\Errors;

use \Exception;

class Controller extends BaseController
{
    protected $request;
    protected $errors;

    /**
     * Controller constructor.
     * @param Requests $request
     * @param Errors $errors
     */
    public function __construct(Requests $request, Errors $errors)
    {
        $this->request = $request;
        $this->errors = $errors;
    }

    /**
     * @param LoginRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(LoginRequest $request)
    {
        if ($request->isMethod('get')) {
            return view('site.auth.login');
        }

        try {
           $response = $this->request->identify($request);
        } catch (Exception $exception) {
            return abort(403);
        }

        if ($response['status'] == 200) {
            return view('site.auth.verify');
        }

        $message = $this->errors->message($response['body']['code']);
        return view('site.auth.login')->withErrors($message);
    }

    /**
     * @param VerifyRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verify(VerifyRequest $request)
    {
        if ($request->isMethod('get')) {
            return view('site.auth.verify');
        }

        try {
            $response = $this->request->verify($request);
        } catch (Exception $exception) {
            return abort(403);
        }

        if ($response['status'] == 200) {
            return redirect()->route('cabinet');
        }

        $message = $this->errors->message($response['body']['code']);
        return view('site.auth.verify')->withErrors($message);
    }

}
