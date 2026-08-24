<?php

declare(strict_types=1);

namespace App\Services\Sola;

use App\Exceptions\SolaUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * Transport for the SOLA billing API.
 *
 * Every request carries two credentials: HTTP Basic for the service account,
 * and an X-Access-Token which is md5("<username> <secret> <body>") over the
 * *exact* JSON bytes of the request. The body is therefore encoded once and
 * sent verbatim — re-encoding it would silently invalidate the signature.
 *
 * The client is stateless: the caller supplies the account id and phone. It
 * knows nothing about cookies or sessions.
 */
final class SolaClient
{
    private readonly string $baseUrl;

    private readonly string $username;

    private readonly string $password;

    private readonly string $secretKey;

    private readonly int $timeout;

    private readonly int $connectTimeout;

    private readonly int $retryTimes;

    private readonly int $retrySleepMs;

    /**
     * @param  array<string, mixed>  $config  The "sola" config section.
     */
    public function __construct(
        private readonly HttpFactory $http,
        array $config,
    ) {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->username = (string) ($config['username'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->timeout = (int) ($config['timeout'] ?? 10);
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 3);
        $this->retryTimes = max(1, (int) ($config['retry_times'] ?? 1));
        $this->retrySleepMs = max(0, (int) ($config['retry_sleep_ms'] ?? 0));
    }

    /**
     * Look up a subscriber by phone and send them a confirmation SMS.
     */
    public function identify(string $phone): SolaResponse
    {
        // Sends an SMS — never retry, a repeat costs the subscriber a message.
        return $this->post('/identify', [
            'phn' => $phone,
            'lang' => $this->locale(),
            'sendsms' => 1,
        ], idempotent: false);
    }

    /**
     * The accounts attached to a phone number, without sending an SMS.
     */
    public function accounts(int $phone): SolaResponse
    {
        return $this->post('/identify', [
            'phn' => $phone,
            'lang' => $this->locale(),
            'sendsms' => 0,
        ]);
    }

    /**
     * Exchange the SMS code for a verified session.
     */
    public function verify(string $phone, string $smsCode): SolaResponse
    {
        // Consumes a one-time code — a retry would fail as "already used".
        return $this->post('/verify', [
            'phn' => $phone,
            'smsCode' => $smsCode,
            'lang' => $this->locale(),
        ], idempotent: false);
    }

    public function abonentInfo(string $accountId): SolaResponse
    {
        return $this->post('/abonent/info', [
            'acc_id' => $accountId,
            'lang' => $this->locale(),
        ]);
    }

    public function devices(string $accountId): SolaResponse
    {
        return $this->post('/device/list', [
            'acc_id' => $accountId,
            'lang' => $this->locale(),
        ]);
    }

    /**
     * @param  string  $begin  "Y-m-d", inclusive
     * @param  string  $end    "Y-m-d", inclusive
     */
    public function payments(string $accountId, string $begin, string $end): SolaResponse
    {
        return $this->post('/acct/payments', [
            'acc_id' => $accountId,
            'pay_begin' => $begin,
            'pay_end' => $end,
            'lang' => $this->locale(),
        ]);
    }

    /**
     * @param  string  $month  "Y-m"
     */
    public function trafficDetail(string $accountId, string $month): SolaResponse
    {
        return $this->post('/traffic/detail', [
            'acc_id' => $accountId,
            'detail_month' => $month,
            'lang' => $this->locale(),
        ]);
    }

    public function availableTariffs(string $accountId): SolaResponse
    {
        return $this->post('/tariff/available', [
            'acc_id' => $accountId,
            'lang' => $this->locale(),
        ]);
    }

    /**
     * The tariffs in force, each carrying the date it started.
     *
     * /abonent/info names the current tariff but never says when it began, and
     * the charge date follows the last tariff change rather than the contract
     * date. This is the only endpoint that reports it: probed live on
     * 2026-08-13, it returned one row whose tariff_id matched curr_tariff_id
     * exactly (docs/api/SOLA_API_REFERENCE.md §14).
     *
     * It answers with the active permits only — not a history.
     */
    public function connectedTariffs(string $accountId): SolaResponse
    {
        return $this->post('/tariff/connected', [
            'acc_id' => $accountId,
            'lang' => $this->locale(),
        ]);
    }

    /**
     * @param  string  $connectionDate  "Y-m-d"
     */
    public function connectTariff(string $accountId, int $tariffId, string $connectionDate): SolaResponse
    {
        // Historically sent without a "lang" field — the signature covers the
        // body byte for byte, so the shape must not drift.
        return $this->post('/tariff/connect', [
            'acc_id' => $accountId,
            'tariff_id' => $tariffId,
            'tariff_conndate' => $connectionDate,
        ], idempotent: false);
    }

    public function addDevice(string $accountId): SolaResponse
    {
        return $this->post('/device/new', [
            'acc_id' => $accountId,
            'lang' => $this->locale(),
        ], idempotent: false);
    }

    public function deleteDevice(string $accountId, string $permitId): SolaResponse
    {
        return $this->post('/device/delete', [
            'acc_id' => $accountId,
            'lang' => $this->locale(),
            'permit_id' => $permitId,
        ], idempotent: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws SolaUnavailableException when the API cannot be reached at all.
     */
    private function post(string $endpoint, array $payload, bool $idempotent = true): SolaResponse
    {
        // Encoded once: these are the bytes we sign and the bytes we send.
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $request = $this->request($idempotent)
            ->withHeaders(['X-Access-Token' => $this->signature($json)])
            ->withBody($json, 'application/json');

        try {
            $response = $request->post($this->baseUrl . $endpoint);
        } catch (ConnectionException $e) {
            throw new SolaUnavailableException($endpoint, $e->getMessage(), $e);
        }

        return new SolaResponse($response->status(), (array) $response->json());
    }

    private function request(bool $idempotent): PendingRequest
    {
        $request = $this->http
            ->withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);

        // A dropped connection on a mutating call leaves the outcome unknown,
        // so only reads are retried.
        if ($idempotent && $this->retryTimes > 1) {
            $request->retry(
                $this->retryTimes,
                $this->retrySleepMs,
                fn(Throwable $e): bool => $e instanceof ConnectionException,
                // retry() throws on any non-2xx once tries > 1, which would turn
                // an ordinary business error — the API answers 400 with
                // {"code":114,"errMsg":"…"} — into an uncaught RequestException
                // and a 500 page. This class's contract is that post() always
                // returns a SolaResponse and the caller inspects failed().
                throw: false,
            );
        }

        return $request;
    }

    private function signature(string $json): string
    {
        return md5("{$this->username} {$this->secretKey} {$json}");
    }

    private function locale(): string
    {
        return App::getLocale();
    }
}
