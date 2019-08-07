<?php


namespace App\Helpers;
use App\Http\Requests\Auth\Verify;
use GuzzleHttp\Client;
use App\Helpers\SetCookie;

use App\Http\Requests\Auth\Login as LoginRequest;
use App\Http\Requests\Auth\Verify as VerifyRequest;

class Requests
{
    protected $url;
    protected $client;
    protected $cookie;
    protected $lang;

    /**
     * Requests constructor.
     * @param \App\Helpers\SetCookie $cookie
     */
    public function __construct(SetCookie $cookie)
    {
        $api = env('API_IP');
        $this->url = "http://{$api}/apipc";
        $this->client = new Client();
        $this->cookie = $cookie;
        $this->lang = 'uz';
    }

    /**
     * @param LoginRequest $request
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function identify(LoginRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/identify";

        $response = $this->client->request($method, $url, [
           'json' => [
               'phn' => $request->getLogin(),
               'lang' => $this->lang
           ]
        ]);

        $data = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];

        if ($data['status'] == 200) {
            $this->cookie->AbonentType($data['body']['abonType']);
            return $data;
        }

        return false;
    }

    /**
     * @param VerifyRequest $request
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verify(VerifyRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/verify";
        $response = $this->client->request($method, $url, [
            'json' => [
                'phn' => $request->getLogin(),
                'smsCode' => $request->getPassword(),
                'lang' => $this->lang
            ]
        ]);

        $data = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];

        if ($response->getBody() == 200) {
            return $data;
        }

        return false;
    }

}