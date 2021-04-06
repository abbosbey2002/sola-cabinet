<?php

namespace App\Helpers;
use App\Http\Requests\Auth\Verify;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Helpers\SetCookie;

use App\Http\Requests\Auth\Login as LoginRequest;
use App\Http\Requests\Auth\Verify as VerifyRequest;
use App\Http\Requests\Abonent\Edit as AbonentEditRequest;
use App\Http\Requests\Wifi\Password as WifiPasswordRequest;
use App\Http\Requests\Payment\History as PaymentHistoryRequest;
use App\Http\Requests\Traffic\Detail as TrafficDetailRequest;
use App\Http\Requests\Tariff\Set as SetTariffRequest;

use Illuminate\Support\Facades\App;


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
        $this->url = "http://{$api}/";
        $this->client = new Client();
        $this->cookie = $cookie;

        $this->token = 'Basic ' . base64_encode(env("SOLA_USERNAME").':'.env('SOLA_PASSWORD'));
    }

    /**
     * @param $requestJSON
     * @return string
     */
    private function generateAuthToken($requestJSON)
    {
        $userName = env('SOLA_USERNAME');
        $secretKey = env('SOLA_SECRET_KEY');
        $json = json_encode($requestJSON);

        $auth = "{$userName} {$secretKey} {$json}";
        return md5($auth);
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
            'lang' => App::getLocale(),
            'sendsms' => 1
        ];

        $response = $this->client->request($method, $url, [
                'json' => $json,
                'http_errors' => false,
                'headers' =>[
                    'X-Access-Token' => $this->generateAuthToken($json),
                    'Authorization' => $this->token,
                    'Content-Type' => 'application/json'
                ]
            ]);

        $request->session()->put('phone', $request->getLogin());
        $this->cookie->setLogin($request->getLogin());
        $this->cookie->setPhone($request->getLogin());
        $data = $this->setData($response);

        if ($response->getStatusCode() == 200) {
            if (count($data['body']['accs']) == 1) {
                $this->cookie->setAccID($data['body']['accs'][0]['accId']);
                $this->cookie->AbonentType($data['body']['accs'][0]['abonType']);
            }
        }

        return $data;
    }


    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function selectUsers(string $method = 'POST')
    {
        $url = "{$this->url}/identify";
        $json = [
            'phn' => $this->cookie->getPhone(),//$request->getLogin(),
            'lang' => App::getLocale(),
            'sendsms' => 0
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);

        return $data;
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
            'phn' => $request->cookie('login'),
            'smsCode' => $request->getCode(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);

        if ($response->getStatusCode() == 200) {
            $this->cookie->verifyUser();
        }
        return $data;
    }

    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function abonentInfo(string $method = 'POST')
    {
        $url = "{$this->url}/abonent/info";

        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
                'json' => $json,
                'http_errors' => false,
                'headers' =>[
                    'X-Access-Token' => $this->generateAuthToken($json),
                    'Authorization' => $this->token,
                    'Content-Type' => 'application/json'
                ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    public function getDevicesList(string $method = 'POST')
    {
        $url = "{$this->url}/device/list";

        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param int $acc_id
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function abonentInfoSelect(int $acc_id, string $method = 'POST')
    {
        $url = "{$this->url}/abonent/info";

        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPayments(string $method = 'POST')
    {
        $url = "{$this->url}/acct/payments";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'pay_month' => Carbon::now()->format('Y-m'),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }


    /**
     * @param PaymentHistoryRequest $request
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentsMonth(PaymentHistoryRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/acct/payments";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'pay_month' => $request->getPayMonth(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTrafficDetail(string $method = 'POST')
    {
        $url = "{$this->url}/traffic/detail";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'detail_month' => Carbon::now()->format('Y-m'),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param TrafficDetailRequest $request
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTrafficDetailMonth(TrafficDetailRequest $request, string $method = 'POST')
    {
        $url = "{$this->url}/traffic/detail";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'detail_month' => $request->getMonth(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTariff(string $method = 'POST')
    {
        $url = "{$this->url}/tariff/available";
        $json = [
            'acc_id' => request()->cookie('account'),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param int $tariff_id
     * @param string $tariff_date
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setTariff(int $tariff_id, string $tariff_date, string $method = 'POST')
    {
        $url = "{$this->url}/tariff/connect";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'tariff_id' => $tariff_id,
            'tariff_conndate' => $tariff_date
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function newDevice(string $method = 'POST')
    {
        $url = "{$this->url}/device/new";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }

    /**
     * @param string $method
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDevices(string $method = 'POST')
    {
        $url = "{$this->url}/device/list";
        $json = [
            'acc_id' => $this->cookie->getAccID(),
            'lang' => App::getLocale()
        ];

        $response = $this->client->request($method, $url, [
            'json' => $json,
            'http_errors' => false,
            'headers' =>[
                'X-Access-Token' => $this->generateAuthToken($json),
                'Authorization' => $this->token,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = $this->setData($response);
        return $data;
    }
}
