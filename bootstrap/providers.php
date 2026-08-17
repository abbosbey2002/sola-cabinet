<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    // Telescope is registered from AppServiceProvider, and only in local —
    // see the note there. Listing it here would load it everywhere.
];
