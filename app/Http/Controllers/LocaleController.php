<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\AbonentSession;
use App\Support\Activity\ActivityRecorder;
use Illuminate\Http\RedirectResponse;

/**
 * Language switcher. Reachable without a verified session, since the login
 * screens carry the same switcher.
 */
final class LocaleController
{
    public function __construct(
        private readonly AbonentSession $session,
        private readonly ActivityRecorder $activity,
    ) {}

    public function update(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, (array) config('app.supported_locales'), true), 404);

        $this->activity->addMeta(['from' => app()->getLocale(), 'to' => $locale]);

        $this->session->setLocale($locale);

        return redirect()->back();
    }
}
