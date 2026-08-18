<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\BulkToggleTariffsRequest;
use App\Services\Sola\SolaClient;
use App\Support\TariffVisibility;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets an admin hide a tariff from /tariffs without billing ever knowing —
 * the hiding happens in this app, not upstream. See TariffVisibility.
 */
final class AdminTariffController
{
    /**
     * A real billing account used only to read the tariff catalog (SRV_ID is
     * a stable id shared across accounts, so which account supplies the list
     * does not matter — see docs/api/SOLA_API_REFERENCE.md §13). Overridable
     * via ?acc_id= for whoever is testing against a different one.
     */
    private const DEFAULT_CATALOG_ACCOUNT = '1252453';

    public function __construct(
        private readonly SolaClient $sola,
        private readonly TariffVisibility $visibility,
        private readonly ViewFactory $view,
    ) {}

    public function index(Request $request): View
    {
        $accountId = (string) $request->query('acc_id', self::DEFAULT_CATALOG_ACCOUNT);

        $response = $this->sola->availableTariffs($accountId);

        if ($response->failed()) {
            session()->flash('danger', $response->errorMessage() ?? trans('errors.unknown'));
        }

        $disabled = $this->visibility->disabledIds();

        return $this->view->make('admin.tariffs', [
            'accountId' => $accountId,
            'tariffs' => (array) $response->get('tariffs', []),
            'disabled' => $disabled,
        ]);
    }

    public function toggle(int $tariffId, Request $request): RedirectResponse
    {
        if ($this->visibility->isEnabled($tariffId)) {
            $this->visibility->disable($tariffId);
        } else {
            $this->visibility->enable($tariffId);
        }

        session()->flash('info', trans('app.admin.toggled'));

        return redirect()->route('admin.tariffs', $request->only('acc_id'));
    }

    /**
     * The row checkboxes feeding this are plain inputs outside any <form> —
     * see resources/js/modules/bulk-select.js — so this route only ever sees
     * ids the admin actually had checked when the bulk form was submitted.
     */
    public function bulkToggle(BulkToggleTariffsRequest $request): RedirectResponse
    {
        foreach ($request->tariffIds() as $tariffId) {
            $request->shouldEnable()
                ? $this->visibility->enable($tariffId)
                : $this->visibility->disable($tariffId);
        }

        session()->flash('info', trans('app.admin.bulk_toggled', ['count' => count($request->tariffIds())]));

        return redirect()->route('admin.tariffs', $request->only('acc_id'));
    }
}
