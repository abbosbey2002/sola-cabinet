<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web3 dashboard module
    |--------------------------------------------------------------------------
    |
    | UI-only until wallet and on-chain settlement are wired. When inactive,
    | the dashboard hides wallet connect, crypto equivalents, and the Web3
    | panel entirely — same pattern as config('iwon.active').
    |
    */

    'active' => (bool) env('WEB3_ACTIVE', false),

    /*
    | Display-only UZS per 1 USDT for the "≈ $X USDT" line under the fiat
    | balance. Omit or zero to hide — never invented from billing data.
    */
    'usdt_rate' => env('WEB3_USDT_RATE') !== null ? (float) env('WEB3_USDT_RATE') : null,

    'network' => env('WEB3_NETWORK', 'Polygon'),

];
