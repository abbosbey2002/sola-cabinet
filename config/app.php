<?php

use Illuminate\Support\Facades\Facade;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Sola'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Kept on UTC to match the behaviour the SOLA API has been fed since 2019.
    | Changing this shifts the month boundaries used for the payment and
    | traffic lookups, so it has to be coordinated with the billing side.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The cabinet ships Russian, Uzbek and English. The active locale is taken
    | from the "lang" cookie by the SetLocale middleware.
    |
    */

    'locale' => env('APP_LOCALE', 'ru'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ru'),

    'supported_locales' => ['ru', 'uz', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | Carbon is aliased because the Blade templates format API dates directly.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'Carbon' => Carbon\Carbon::class,
    ])->toArray(),

];
