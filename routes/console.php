<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\Telescope;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Telescope writes an entry per request, query, view and outgoing API call —
| a single page load costs about thirty rows. Left alone the SQLite file
| grows without limit, so old entries are dropped daily.
|
| This only runs if something invokes "php artisan schedule:run" every
| minute; the Docker setup has no cron, so locally the prune is a manual
| "php artisan telescope:prune" when the file gets big.
|
*/

// The guard matches the one in AppServiceProvider: outside local Telescope is
// never registered, so its command does not exist and scheduling it would
// queue up a task that can only fail.
if (app()->environment('local') && class_exists(Telescope::class)) {
    Schedule::command('telescope:prune --hours=48')->daily();
}
