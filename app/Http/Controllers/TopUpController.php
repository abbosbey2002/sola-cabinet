<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TopUpRequest;
use App\Support\AbonentProfile;
use App\Support\IwonCheckout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * "Hisobni to'ldirish" — top up the balance via iWon's hosted Uzcard/Humo form.
 *
 * iWon has no callback and no signature: the browser is sent to their hosted
 * form and lands back on returnUrl when done. We do not poll or confirm that
 * redirect — billing credits the balance on its own schedule, same as Payme/
 * Click entered elsewhere.
 */
final class TopUpController extends Controller
{
    public function index(): View
    {
        abort_unless((bool) config('iwon.active'), 404);

        $profile = AbonentProfile::from($this->sola->abonentInfo($this->accountId()), $this->session->billingLogin());
        abort_if($profile->isLegalEntity(), 403);

        return $this->view->make('cabinet.topup', [
            'profile' => $profile,
            'accounts' => $this->accounts(),
        ]);
    }

    public function store(TopUpRequest $request): RedirectResponse
    {
        abort_unless((bool) config('iwon.active'), 404);

        abort_if(AbonentProfile::from($this->sola->abonentInfo($this->accountId()))->isLegalEntity(), 403);

        $accountId = $this->accountId();
        $amount = $request->amount();

        $balanceBefore = AbonentProfile::from($this->sola->abonentInfo($accountId))->balance();

        $redirect = IwonCheckout::fromConfig()->redirectUrl($amount, $accountId, route('cabinet'));

        Log::info('iwon.topup.initiated', [
            'account_id' => $accountId,
            'amount_som' => $amount,
            'balance_before' => $balanceBefore,
            'additional_id' => $redirect->additionalId,
        ]);

        return redirect()->away($redirect->url);
    }
}
