<?php

namespace App\Helpers;
use App\Http\Requests\Auth\Verify;
use GuzzleHttp\Client;
use App\Helpers\SetCookie;

use App\Http\Requests\Auth\Login as LoginRequest;
use App\Http\Requests\Auth\Verify as VerifyRequest;
use App\Http\Requests\Abonent\Edit as AbonentEditRequest;
use App\Http\Requests\Wifi\Password as WifiPasswordRequest;
use App\Http\Requests\Payment\History as PaymentHistoryRequest;
use App\Http\Requests\Traffic\Detail as TrafficDetailRequest;
use App\Http\Requests\Tariff\Set as SetTariffRequest;


class Requests
{
    protected $url;
    protected $client;
    protected $cookie;
    protected $lang;
    protected $token;

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

        $this->token = 'Basic ' . base64_encode(env("SOLA_USERNAME").':'.env('SOLA_PASSWORD'));
    }

    /**
     * @param $requestJSON
     * @return string
     */
    private function generateAuthToken($requestJSON)
    {
        $userName = env('SOLA_USERNAME');
        $secretKey = env('SOLA_PASSWORD');
        $json = json_encode($requestJSON);

        $auth = "{$userName} {$secretKey} {$json}";
        return md5($auth);
    }


    /**
     * @param $json
     * @return array
     */
    private function header($json): array
    {
        return [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->token,
                'Authorization' => $this->generateAuthToken($json)
            ]
        ];
    }

    /**
     * @param $response
     * @return array
     */
    private function setData($response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
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

        $json = [
            'phn' => $request->getLogin(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        $this->cookie->setLogin($request->getLogin());

        $data = $this->setData($response);

        if ($response->getStatusCode() == 200) {
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

        $json = [
            'phn' => $request->getLogin(),
            'smsCode' => $request->getPassword(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getBody() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function abonentInfo(string $method = 'POST')
    {
        $url = "{$this->url}/abonent/info";

        $json = [
            'phn' => $this->cookie->getLogin(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }


    /**
     * @param AbonentEditRequest $request
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function abonentEdit(AbonentEditRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/abonent/edit";

        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'email' => $request->getEmail(),
            'phone' => $request->getPhone()
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function balance(string $method = 'POST')
    {
        $url = "{$this->url}/acct/balance";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param WifiPasswordRequest $request
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function changeWifiPassword(WifiPasswordRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/acct/wifipassword";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'curr_password' => $request->getCurrentPassword(),
            'new_password' => $request->getNewPassword(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }


    /**
     * @param PaymentHistoryRequest $request
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPayments(PaymentHistoryRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/acct/payments";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'pay_month' => $request->getPayMonth(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param TrafficDetailRequest $request
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTrafficDetail(TrafficDetailRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/traffic/detail";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'detail_month' => $request->getMonth(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTariff(string $method = 'POST')
    {
        $url = "{$this->url}/tariff/available";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TariffConnected(string $method = 'POST')
    {
        $url = "{$this->url}/tariff/connected";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param SetTariffRequest $request
     * @param $tariff_id
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setTariff(SetTariffRequest $request, $tariff_id, string $method = 'POST')
    {
        $url = "{$this->url}/tariff/connect";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'tariff_id' => $tariff_id,
            'tariff_conndate' => $request->getTariffDate()
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param $tariff_id
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TariffDisConnect($tariff_id, string $method = 'POST')
    {
        $url = "{$this->url}/tariff/disconnect";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'tariff_id' => $tariff_id
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function newDevice(string $method = 'POST')
    {
        $url = "{$this->url}/device/new";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }

    /**
     * @param string $method
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDevices(string $method = 'POST')
    {
        $url = "{$this->url}/device/list";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => $this->lang
        ];

        $response = $this->client->request($method, $url, [
            $this->header($json)
        ]);

        if ($response->getStatusCode() == 200) {
            return $this->setData($response);
        }

        return false;
    }




}