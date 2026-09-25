<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The cabinet itself has no database — every screen is rendered from the
    | SOLA billing API. This connection exists for Telescope, which needs
    | somewhere to keep the entries it records while debugging.
    |
    | A file-backed SQLite database keeps that promise intact: no server to
    | run, nothing to provision, and the file lives outside version control.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        /*
         * The activity journal, tariff-change history and admin audit trail
         * (config/activity.php). PostgreSQL in production: one row per page
         * view outgrows a SQLite file shared with the admin allow-list, and the
         * statistics pages run aggregate queries over months of it.
         *
         * ACTIVITY_DB_DRIVER exists for the test suite, which points this at
         * an in-memory SQLite database (phpunit.xml) so CI needs no server.
         * The activity migrations live in their own folder and are run with
         * `php artisan activity:migrate`, never by a bare `migrate`.
         */
        'activity' => [
            'driver' => env('ACTIVITY_DB_DRIVER', 'pgsql'),
            'url' => env('ACTIVITY_DB_URL'),
            'host' => env('ACTIVITY_DB_HOST', '127.0.0.1'),
            'port' => env('ACTIVITY_DB_PORT', '5432'),
            'database' => env('ACTIVITY_DB_DATABASE', 'cabinet_activity'),
            'username' => env('ACTIVITY_DB_USERNAME', 'cabinet'),
            'password' => env('ACTIVITY_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('ACTIVITY_DB_SSLMODE', 'prefer'),
            'foreign_key_constraints' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Left in place because Laravel resolves this key when the cache or queue
    | is pointed at Redis. Nothing in the cabinet uses it today — cache, queue
    | and sessions are all file-driven.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
