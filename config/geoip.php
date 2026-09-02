<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | IP geolocation for connected devices
    |--------------------------------------------------------------------------
    |
    | Billing's /device/list may include a public `ip` per permit. When this
    | flag is on, the cabinet asks ipwho.is for a city/country label and
    | caches the answer — it is display-only and never sent back to billing.
    |
    */
    'active' => (bool) env('GEOIP_ACTIVE', false),

    /** Request timeout in seconds — a slow lookup must not block the page. */
    'timeout' => (int) env('GEOIP_TIMEOUT', 2),

    /** Cache successful lookups for this many hours. */
    'cache_hours' => (int) env('GEOIP_CACHE_HOURS', 24),

];
