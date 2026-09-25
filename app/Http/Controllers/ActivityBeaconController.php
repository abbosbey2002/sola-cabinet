<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ActivityBeaconRequest;
use App\Support\Activity\ActivityRecorder;
use App\Support\Activity\Outcome;
use Illuminate\Http\Response;

/**
 * POST /activity — the browser's half of the journal (print, search, a dialog
 * closed without confirming). The server cannot see those on its own.
 *
 * Always 204, recorded or not: the page sent this with sendBeacon and is not
 * listening for an answer.
 */
final class ActivityBeaconController
{
    public function __construct(private readonly ActivityRecorder $recorder) {}

    public function store(ActivityBeaconRequest $request): Response
    {
        $this->recorder->record($request, $request->event(), Outcome::Ok, 204, meta: $request->meta());

        return response()->noContent();
    }
}
