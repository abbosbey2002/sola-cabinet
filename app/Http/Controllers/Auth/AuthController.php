<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyRequest;
use App\Support\ErrorMessages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Phone + SMS login against the SOLA API. There are no local user records.
 */
final class AuthController extends Controller
{
    /**
     * Ask the API to text a confirmation code to the subscriber.
     */
    public function login(LoginRequest $request): View
    {
        if ($request->isMethod('get')) {
            return $this->view->make('auth.login');
        }

        $phone = $request->phone();
        $response = $this->sola->identify($phone);

        if ($response->failed()) {
            return $this->view->make('auth.login')
                ->withErrors(ErrorMessages::for($response->errorCode()));
        }

        $this->session->setLogin($phone);
        $this->session->setPhone((int) $phone);

        // Only one account: nothing to choose later, select it right away.
        $accounts = (array) $response->get('accs', []);

        if (count($accounts) === 1) {
            $this->selectAccount($accounts[0]);
        }

        // The verify screen masks and echoes the number back to the user.
        $request->session()->put('phone', $phone);

        return $this->view->make('auth.verify');
    }

    /**
     * Exchange the SMS code for a verified session.
     */
    public function verify(VerifyRequest $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return $this->view->make('auth.verify');
        }

        $response = $this->sola->verify($this->session->login(), $request->code());

        if ($response->failed()) {
            return $this->view->make('auth.verify')
                ->withErrors(ErrorMessages::for($response->errorCode()));
        }

        $this->session->markVerified();

        return $this->accountChoice();
    }

    /**
     * The account picker, shown when a phone carries more than one account.
     */
    public function accountChoice(): View|RedirectResponse
    {
        $response = $this->accounts();

        if (count((array) $response->get('accs', [])) === 1) {
            return redirect()->route('cabinet');
        }

        return $this->view->make('auth.select_account', ['response' => $response]);
    }

    /**
     * Switch the session to one of the subscriber's accounts.
     *
     * The id comes straight off a URL, so it is matched against the accounts
     * the API returns for this phone — otherwise any signed-in visitor could
     * read another subscriber's balance and delete their devices.
     */
    public function switchAccount(string $accountId): RedirectResponse
    {
        $account = collect((array) $this->accounts()->get('accs', []))
            ->first(fn (mixed $account): bool => is_array($account)
                && (string) ($account['accId'] ?? '') === $accountId);

        abort_if($account === null, 403);

        $this->selectAccount($account);

        return redirect()->route('cabinet');
    }

    public function logout(): RedirectResponse
    {
        $this->session->flush();

        return redirect()->route('login');
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function selectAccount(array $account): void
    {
        $this->session->setAccountId((string) ($account['accId'] ?? ''));
        $this->session->setAbonentType((int) ($account['abonType'] ?? 0));
        $this->session->setBillingLogin((string) ($account['login'] ?? ''));
        $this->session->setFullName($this->resolveFullName($account));
    }

    /**
     * The subscriber's display name for this account — sourced from
     * /identify's own `abonName`/`login`, not /abonent/info's `name`.
     *
     * /abonent/info's `name` is not reliable as a display name: observed
     * live 2026-08-28 on account 1000033 (abonType "1", one-off) as the
     * literal string "Разовый абонент" — billing's own generic label for the
     * account type, filled in instead of leaving `name` null the way
     * docs/api/SOLA_API.md §3 documents for accounts with no name on file.
     * Shown as-is it read as the subscriber's own name in the header and
     * account switcher. `abonName` is documented as "often blank" rather
     * than wrong, and `login` is /identify's reliable fallback for that same
     * row — the same `abonName ?: login` pattern `x-account-menu`'s "switch
     * account" list already uses, now shared by whichever account is
     * actually selected.
     *
     * @param  array<string, mixed>  $account
     */
    private function resolveFullName(array $account): string
    {
        $abonName = (string) ($account['abonName'] ?? '');

        return $abonName !== '' ? $abonName : (string) ($account['login'] ?? '');
    }
}
