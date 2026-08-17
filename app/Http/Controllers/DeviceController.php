<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Sola\SolaResponse;
use App\Support\AbonentProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * "Qurilmalar" — the MAC permits registered on the account.
 *
 * Both write actions are POST. They used to be links: adding a device costs
 * real money, and a GET link carries no CSRF token, so any page a signed-in
 * subscriber opened could have billed them for a permit.
 */
final class DeviceController extends Controller
{
    public function index(): View
    {
        $accountId = $this->accountId();

        // /device/list answers with the permits *and* what a further permit
        // costs, so both come out of the one call.
        $devices = $this->sola->devices($accountId);

        return $this->view->make('cabinet.devices', [
            'profile' => AbonentProfile::from($this->sola->abonentInfo($accountId)),
            'accounts' => $this->accounts(),
            'devices' => (array) $devices->get('devices', []),
            'connectCost' => $devices->get('connect_cost'),
        ]);
    }

    /**
     * Register an extra MAC address on the account.
     *
     * The permanence check is here and not only in the view: cabinet/devices
     * hides the button from a temporary or one-off subscriber, but the form is
     * reachable with a valid CSRF token by anyone signed in — and this one
     * costs money.
     */
    public function store(): RedirectResponse
    {
        abort_unless($this->session->isPermanent(), 403);

        return $this->flashAndReturn(
            $this->sola->addDevice($this->accountId()),
            trans('app.header.success_device'),
        );
    }

    /**
     * Release a device permit. The API scopes the permit to the account, and
     * the account itself is only ever set from the verified subscriber's own
     * list, so a forged permit id cannot reach someone else's device.
     */
    public function destroy(string $permitId): RedirectResponse
    {
        abort_unless($this->session->isPermanent(), 403);

        return $this->flashAndReturn(
            $this->sola->deleteDevice($this->accountId(), $permitId),
            trans('app.header.success_deleted'),
        );
    }

    private function flashAndReturn(SolaResponse $response, string $successText): RedirectResponse
    {
        if ($response->successful()) {
            $this->flashInfo($successText);
        } else {
            $this->flashDanger($response->errorMessage() ?? trans('errors.unknown'));
        }

        return redirect()->route('devices');
    }
}
