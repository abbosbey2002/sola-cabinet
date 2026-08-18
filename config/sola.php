<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SOLA Billing API
    |--------------------------------------------------------------------------
    |
    | Every page of the cabinet is rendered from this API — there is no local
    | database. Requests are authenticated twice: HTTP Basic with the service
    | account, plus an X-Access-Token signature over the exact JSON body.
    |
    */

    'base_url' => env('SOLA_BASE_URL', 'http://'.env('API_IP', '127.0.0.1')),

    'username' => env('SOLA_USERNAME'),

    'password' => env('SOLA_PASSWORD'),

    'secret_key' => env('SOLA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Offline Development
    |--------------------------------------------------------------------------
    |
    | Billing sits on SOLA's internal network, so off the VPN every call times
    | out and — the cabinet having no database of its own — every page becomes
    | a 503. With this on, App\Services\Sola\FakeSolaServer answers the API in
    | process instead. It is honoured in the local environment only, so it can
    | never be switched on by accident on a deployed host.
    |
    */

    'fake' => (bool) env('SOLA_FAKE', false),

    /*
    |--------------------------------------------------------------------------
    | Login Bypass
    |--------------------------------------------------------------------------
    |
    | Skips the SMS step only — /identify and /verify are answered in process,
    | always into the same test account (see FakeLoginServer::ACCOUNT_ID).
    | Every other endpoint still hits the real API, so the VPN must be up.
    | Ignored when SOLA_FAKE is also on, which fakes the whole API instead.
    |
    */

    'fake_login' => (bool) env('SOLA_FAKE_LOGIN', false),

    /*
    |--------------------------------------------------------------------------
    | Transport
    |--------------------------------------------------------------------------
    |
    | The API sits on the internal network, so it is expected to answer fast.
    | Retries cover a dropped connection only — never a business-level error,
    | which the API reports with a non-200 status and an "code" body field.
    |
    */

    'timeout' => (int) env('SOLA_TIMEOUT', 10),

    'connect_timeout' => (int) env('SOLA_CONNECT_TIMEOUT', 3),

    'retry_times' => (int) env('SOLA_RETRY_TIMES', 2),

    'retry_sleep_ms' => (int) env('SOLA_RETRY_SLEEP_MS', 150),

    /*
    |--------------------------------------------------------------------------
    | Contact Details
    |--------------------------------------------------------------------------
    |
    | Rendered in the page footer and side menu.
    |
    */

    'call_center' => env('CALL_CENTER'),

    'call_phone' => env('CALL_PHONE'),

    /*
    |--------------------------------------------------------------------------
    | Entry Points
    |--------------------------------------------------------------------------
    |
    | The dashboard's bottom row — promotions, the loyalty programme and
    | support chat. Each card is hidden until its destination is configured,
    | so an unfinished section never ships as a dead link.
    |
    | The chat widget is loaded from the provider's own script. Set both the
    | URL and, if the provider needs it, the widget id.
    |
    */

    /*
     * The speed test lives on SOLA's Ookla-hosted subdomain. It appears in the
     * navigation and again on the services page, so the address is held here
     * rather than written into two templates that will drift apart.
     */
    'speedtest_url' => env('SOLA_SPEEDTEST_URL', 'https://sola.speedtestcustom.com/'),

    'promo_url' => env('SOLA_PROMO_URL'),

    'loyalty_url' => env('SOLA_LOYALTY_URL'),

    'chat' => [
        'url' => env('SOLA_CHAT_URL'),
        'script' => env('SOLA_CHAT_SCRIPT'),
    ],

];
