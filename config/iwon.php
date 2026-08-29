<?php

return [

    /*
    |--------------------------------------------------------------------------
    | iWon Business — card top-up
    |--------------------------------------------------------------------------
    |
    | A plain browser redirect, not an API integration: no signature, no
    | server-to-server call, no callback. The subscriber's browser is sent to
    | iWon's own hosted form (Uzcard/Humo, one-step authorize+capture) and
    | comes back to `topup.return`, which confirms the top-up the same way
    | Payme/Click confirmations already work everywhere else in this app: by
    | asking billing for the current balance, never by trusting the redirect.
    |
    | See docs/api/iwon-api.md for the provider's own reference.
    |
    */

    'active' => (bool) env('IWON_ACTIVE', false),

    'service_id' => env('IWON_SERVICE_ID'),

    /*
    | Which key inside `transactionParams` iWon reads as the ISP billing
    | account to credit. Confirmed with iWon as `acc_id` for this merchant
    | (SOLA) — if that ever changes, only this value needs to, not the code
    | that builds the redirect (App\Support\IwonCheckout).
    */
    'account_param' => env('IWON_ACCOUNT_PARAM', 'acc_id'),

    'currency' => env('IWON_CURRENCY', 'UZS'),

    'frame_url' => env('IWON_FRAME_URL', 'https://business-frame.iwon.uz'),

];
