<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Hisobni to'ldirish" — the iWon redirect, and the stateless return check.
 */
final class TopUpTest extends TestCase
{
    private const ACCOUNT_ID = '1001';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Deterministic regardless of the real .env this app deploys with —
        // matches the fake SOLA_* credentials phpunit.xml already stubs.
        config([
            'iwon.active' => true,
            'iwon.service_id' => '883',
            'iwon.account_param' => 'acc_id',
            'iwon.currency' => 'UZS',
            'iwon.frame_url' => 'https://business-frame.iwon.uz',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function the_page_is_closed_when_iwon_is_not_active(): void
    {
        config(['iwon.active' => false]);

        $this->verifiedSubscriber()->get('/topup')->assertNotFound();
    }

    #[Test]
    public function the_page_is_reachable_when_iwon_is_active(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/topup')
            ->assertOk()
            ->assertSee(trans('app.topup.submit'));
    }

    #[Test]
    public function submitting_an_amount_redirects_to_iwons_hosted_form(): void
    {
        $this->fakeSola();

        $response = $this->verifiedSubscriber()
            ->post('/topup', ['amount' => '10000']);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://business-frame.iwon.uz/?', (string) $location);

        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

        // 10 000 so'm → 1 000 000 tiyin, never float-truncated.
        $this->assertSame('1000000', $query['amount']);
        $this->assertSame('UZS', $query['currency']);
        $this->assertSame('883', $query['serviceId']);
        $this->assertSame(route('topup.return'), $query['returnUrl']);

        $params = json_decode((string) $query['transactionParams'], true);
        $this->assertSame(self::ACCOUNT_ID, $params['acc_id']);
        // Digits only, never longer than iWon's 17-character ceiling.
        $this->assertMatchesRegularExpression('/^\d{1,17}$/', $params['additional_id']);
    }

    /**
     * amount-mask.js formats the field as "10 000" while the subscriber
     * types (resources/js/modules/amount-mask.js), so that is what actually
     * reaches the server — TopUpRequest::prepareForValidation() has to
     * strip the spaces back out before `numeric` gets to judge it.
     */
    #[Test]
    public function a_space_formatted_amount_is_accepted_and_parsed_correctly(): void
    {
        $this->fakeSola();

        $response = $this->verifiedSubscriber()
            ->post('/topup', ['amount' => '10 000']);

        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        // Same 1 000 000 tiyin as the unspaced "10000" case — the mask must
        // not change what actually gets charged.
        $this->assertSame('1000000', $query['amount']);
    }

    #[Test]
    public function submitting_a_store_request_stashes_a_pending_cookie(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 5000]),
        ]);

        $response = $this->verifiedSubscriber()->post('/topup', ['amount' => '10000']);

        $response->assertCookie('pending_topup');
    }

    /**
     * With no callback from iWon and no database, this log line is the only
     * record support can ever produce for a subscriber's "I paid but wasn't
     * credited" — see IwonRedirect/TopUpController's own docblocks.
     */
    #[Test]
    public function initiating_a_topup_is_logged(): void
    {
        Log::spy();

        $this->fakeSola([
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 5000]),
        ]);

        $this->verifiedSubscriber()->post('/topup', ['amount' => '10000']);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'iwon.topup.initiated'
                && $context['account_id'] === self::ACCOUNT_ID
                && $context['amount_som'] === 10000.0
                && $context['balance_before'] === 5000.0
                && is_string($context['additional_id']))
            ->once();
    }

    #[Test]
    public function a_confirmed_topup_is_logged(): void
    {
        Log::spy();

        $this->fakeSola([
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 35000]),
        ]);

        $this->verifiedSubscriber()
            ->withCookie('pending_topup', $this->pendingCookie(balanceBefore: 25000, amount: 10000))
            ->get('/topup/return');

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'iwon.topup.checked'
                && $context['credited'] === true)
            ->once();
    }

    #[Test]
    public function an_amount_below_the_minimum_is_rejected(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->post('/topup', ['amount' => '500'])
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_non_numeric_amount_is_rejected(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->post('/topup', ['amount' => 'abc'])
            ->assertSessionHasErrors('amount');
    }

    #[Test]
    public function the_return_page_shows_success_once_the_balance_has_grown(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 35000]),
        ]);

        $response = $this->verifiedSubscriber()
            ->withCookie('pending_topup', $this->pendingCookie(balanceBefore: 25000, amount: 10000))
            ->get('/topup/return');

        $response->assertOk()
            ->assertSee(trans('app.topup.success_title'))
            ->assertCookieExpired('pending_topup');
    }

    #[Test]
    public function the_return_page_keeps_checking_while_still_within_the_wait_window(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $this->fakeSola([
            // Balance never moved.
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 25000]),
        ]);

        $response = $this->verifiedSubscriber()
            ->withCookie('pending_topup', $this->pendingCookie(
                balanceBefore: 25000,
                amount: 10000,
                initiatedAt: '2026-08-28 09:59:00',
            ))
            ->get('/topup/return');

        $response->assertOk()
            ->assertSee(trans('app.topup.checking_title'))
            ->assertSee('http-equiv="refresh"', escape: false);
    }

    #[Test]
    public function the_return_page_gives_up_after_the_wait_window(): void
    {
        Carbon::setTestNow('2026-08-28 10:10:00');

        $this->fakeSola([
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 25000]),
        ]);

        $response = $this->verifiedSubscriber()
            ->withCookie('pending_topup', $this->pendingCookie(
                balanceBefore: 25000,
                amount: 10000,
                initiatedAt: '2026-08-28 10:00:00',
            ))
            ->get('/topup/return');

        $response->assertOk()
            ->assertSee(trans('app.topup.timeout_title'))
            ->assertDontSee('http-equiv="refresh"', escape: false);
    }

    #[Test]
    public function the_return_page_without_a_pending_cookie_sends_the_subscriber_back(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/topup/return')
            ->assertRedirect(route('topup'));
    }

    /**
     * The account switcher lets a subscriber change the "current" account
     * mid-flow (a second tab, a business+personal pair). A snapshot taken
     * for one account must never be compared against a different account's
     * balance — that comparison is meaningless and could show a false
     * success. See TopUpController::checkReturn().
     */
    #[Test]
    public function the_return_page_ignores_a_pending_cookie_from_a_different_account(): void
    {
        $this->fakeSola([
            // The CURRENT account's balance happens to already clear the
            // OTHER account's snapshot — exactly the case that must not
            // read as success.
            '*/abonent/info' => Http::response(['name' => 'Tester', 'saldo' => 999999]),
        ]);

        $this->verifiedSubscriber()
            ->withCookie('pending_topup', $this->pendingCookie(
                balanceBefore: 25000,
                amount: 10000,
                accountId: '9999',
            ))
            ->get('/topup/return')
            ->assertRedirect(route('topup'));
    }

    #[Test]
    public function the_pay_card_offers_iwon_when_the_balance_is_low(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 5000,
                'curr_tariff_name' => 'Home 100',
                'tariff_price' => '25000',
                'contract_number' => 'D-100145',
                'charge_date' => now()->addDays(5)->format('Y-m-d'),
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee(trans('app.topup.pay_card_button'));
    }

    /**
     * The actual bug behind this: account 1336708's real /abonent/info
     * response (observed live 2026-08-28) has no `contract_number` field at
     * all — only `contract_id`, a different field. pay-card used to be
     * gated as a whole on a contract number being present, so on this real
     * account the button was invisible despite a low balance and
     * iwon.active being on. Regression test for the fix in pay-card.blade.php
     * and cabinet/index.blade.php's calling condition.
     */
    #[Test]
    public function the_pay_card_offers_iwon_even_without_a_contract_number(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'TEST PAYMENTS',
                'saldo' => 0,
                'curr_tariff_name' => 'Smart 50',
                'tariff_price' => '125000',
                // Deliberately no contract_number/contract_num/etc.
                'charge_date' => now()->addDays(5)->format('Y-m-d'),
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee(trans('app.topup.pay_card_button'))
            // The card still reads as one deliberate piece (icon + a line of
            // text) rather than a bare button in an otherwise-empty tinted
            // box, now that the contract half has nothing to show.
            ->assertSee(trans('app.topup.pay_card_title'))
            ->assertSee(trans('app.topup.pay_card_hint'))
            ->assertDontSee(trans('app.dash.contract'));
    }

    #[Test]
    public function no_pay_card_at_all_without_a_contract_number_when_iwon_is_inactive(): void
    {
        config(['iwon.active' => false]);

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'TEST PAYMENTS',
                'saldo' => 0,
                'curr_tariff_name' => 'Smart 50',
                'tariff_price' => '125000',
                'charge_date' => now()->addDays(5)->format('Y-m-d'),
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertDontSee(trans('app.dash.contract'))
            ->assertDontSee(trans('app.topup.pay_card_button'));
    }

    #[Test]
    public function the_pay_card_hides_iwon_when_it_is_not_active(): void
    {
        config(['iwon.active' => false]);

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 5000,
                'curr_tariff_name' => 'Home 100',
                'tariff_price' => '25000',
                'contract_number' => 'D-100145',
                'charge_date' => now()->addDays(5)->format('Y-m-d'),
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertDontSee(trans('app.topup.pay_card_button'));
    }

    private function pendingCookie(
        float $balanceBefore,
        float $amount,
        ?string $initiatedAt = null,
        string $accountId = self::ACCOUNT_ID,
    ): string {
        return (string) json_encode([
            'account_id' => $accountId,
            'balance_before' => $balanceBefore,
            'amount' => $amount,
            'initiated_at' => Carbon::parse($initiatedAt ?? now())->toIso8601String(),
            'additional_id' => '12345',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeSola(array $overrides = []): void
    {
        Http::fake($overrides + [
            '*/identify' => Http::response([
                'accs' => [['accId' => 1001, 'abonType' => 2, 'abonName' => 'Tester Testov']],
            ]),
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
            ]),
            '*/device/list' => Http::response(['devices' => []]),
            '*/acct/payments' => Http::response(['payments' => []]),
            '*' => Http::response([], 200),
        ]);
    }

    private function verifiedSubscriber(): self
    {
        return $this->withCookies([
            'verify' => '1',
            'account' => self::ACCOUNT_ID,
            'login' => '998901234567',
            'phone' => '998901234567',
            'data' => json_encode(['type' => 2]),
        ]);
    }
}
