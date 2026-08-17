<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Sola\SolaClient;
use App\Services\Sola\SolaResponse;
use App\Support\AbonentSession;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Shared wiring for the cabinet pages.
 *
 * Every page is rendered from the SOLA API for the account currently selected
 * in the cookie session. There used to be two template sets picked from the
 * User-Agent; the templates are now one responsive set, so the view factory is
 * injected directly and the pages carry their own breakpoints.
 */
abstract class Controller
{
    private ?SolaResponse $accounts = null;

    public function __construct(
        protected readonly SolaClient $sola,
        protected readonly AbonentSession $session,
        protected readonly ViewFactory $view,
    ) {}

    /**
     * Every account attached to the subscriber's phone — the account switcher
     * in the page header is built from it. Resolved once per request.
     */
    protected function accounts(): SolaResponse
    {
        return $this->accounts ??= $this->sola->accounts($this->session->phone());
    }

    protected function accountId(): string
    {
        return $this->session->accountId();
    }

    protected function flashInfo(string $text): void
    {
        session()->flash('info', $text);
    }

    protected function flashDanger(string $text): void
    {
        session()->flash('danger', $text);
    }
}
