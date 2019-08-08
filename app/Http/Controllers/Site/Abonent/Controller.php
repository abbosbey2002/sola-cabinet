<?php

namespace App\Http\Controllers\Site\Abonent;

use Illuminate\Http\Request as Req;
use App\Http\Controllers\Controller as BaseController;

use App\Helpers\Requests;
use App\Helpers\Errors;

use App\Http\Requests\Abonent\Edit as EditRequest;
use \Exception;

class Controller extends BaseController
{
    protected $requests;
    protected $errors;

    /**
     * Controller constructor.
     * @param Requests $requests
     * @param Errors $errors
     */
    public function __construct(Requests $requests, Errors $errors)
    {
        $this->requests = $requests;
        $this->errors = $errors;
    }

    /**
     * @param EditRequest $request
     * @return \Illuminate\Http\RedirectResponse|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(EditRequest $request)
    {
        try {
            $response = $this->requests->abonentEdit($request);
        } catch (Exception $exception) {
            return abort(403);
        }

        if ($response['body'] == 200) {
            return redirect()->back()->with('info', trans('site.messages.abonent.updated'));
        }

        $message = $this->errors->message($response['body']['code']);

        return redirect()->back()->withErrors($message);
    }
}
