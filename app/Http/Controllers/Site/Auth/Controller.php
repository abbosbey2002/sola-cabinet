<?php

namespace App\Http\Controllers\Site\Auth;

use Illuminate\Http\Request as Req;
use App\Http\Controllers\Controller as BaseController;

use App\Http\Requests\Auth\Login as LoginRequest;
use App\Http\Requests\Auth\Verify as VerifyRequest;

use App\Helpers\Requests;
use App\Helpers\SetCookie;
use App\Helpers\Errors;

use \Exception;

class Controller extends BaseController
{
    protected $request;
    protected $errors;
    protected $cookie;

    /**
     * Controller constructor.
     * @param Requests $request
     * @param Errors $errors
     * @param SetCookie $cookie
     */
    public function __construct(Requests $request, Errors $errors, SetCookie $cookie)
    {
        $this->request = $request;
        $this->errors = $errors;
        $this->cookie = $cookie;
    }

    /**
     * @param LoginRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(LoginRequest $request)
    {
        if ($request->isMethod('get')) {
            return $this->view('auth.login', null);
        }

        try {
           $response = $this->request->identify($request);
        } catch (Exception $exception) {
            //return abort(403);
        }

        //return $response;

        if ($response['status'] == 200) {
            return $this->view('auth.verify', null);
        }

        $message = $this->errors->message($response['body']['code']);
        return $this->view('auth.login', null)->withErrors($message);
    }

    /**
     * @param VerifyRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verify(VerifyRequest $request)
    {
        if ($request->isMethod('get')) {
            return $this->view('auth.verify', null);
        }

        try {
            $response = $this->request->verify($request);
        } catch (Exception $exception) {
            //return abort(403);
        }


        if ($response['status'] == 200) {
            return $this->selectAccount();
            //return redirect()->route('cabinet');
        }

        $message = $this->errors->message($response['body']['code']);
        return $this->view('auth.verify', null)->withErrors($message);
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function selectAccount()
    {
        try {
            $response = $this->request->selectUsers();
        } catch (Exception $exception) {
            return abort(403);
        }

        if (count($response['body']['accs']) == 1) {
            return redirect()->route('cabinet');
        }

        return $this->view('auth.select_account', compact('response'));
    }

    /**
     * @param int $acc_id
     * @param int $type
     * @return \Illuminate\Http\RedirectResponse|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function selectAccountSet(int $acc_id, int $type)
    {
        $this->cookie->setAccID($acc_id);
        $this->cookie->AbonentType($type);

        try {
            $info = $this->request->abonentInfo();
        } catch (Exception $exception) {
            return abort(403);
        }

        //return $info;
        $this->cookie->setFullName($info['body']['name']);

        return redirect()->route('cabinet');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout()
    {
        $this->cookie->logout();
        return redirect()->route('login');
    }

}
