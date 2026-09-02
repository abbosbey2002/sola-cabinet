<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Hisobni to'ldirish" — the iWon redirect to their hosted card form.
 */
final class TopUpTest extends TestCase
{
    private const ACCOUNT_ID = '1001';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'iwon.active' => true,
            'iwon.service_id' => '883',
            'iwon.account_param' => 'acc_id',
            'iwon.currency' => 'UZS',
            'iwon.frame_url' => 'https://business-frame.iwon.uz',
        ]);
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
            ->assertSee(trans('app.topup.submit'))
            ->assertDontSee('u-page-head__title', escape: false)
            ->assertSee(trans('app.topup.title'));
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

        $this->assertSame('1000000', $query['amount']);
        $this->assertSame('UZS', $query['currency']);
        $this->assertSame('883', $query['serviceId']);
        $this->assertSame(route('cabinet'), $query['returnUrl']);

        $params = json_decode((string) $query['transactionParams'], true);
        $this->assertSame(self::ACCOUNT_ID, $params['acc_id']);
        $this->assertMatchesRegularExpression('/^\d{1,17}$/', $params['additional_id']);
    }

    #[Test]
    public function a_space_formatted_amount_is_accepted_and_parsed_correctly(): void
    {
        $this->fakeSola();

        $response = $this->verifiedSubscriber()
            ->post('/topup', ['amount' => '10 000']);

        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('1000000', $query['amount']);
    }

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
    public function the_return_route_is_not_offered(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/topup/return')->assertNotFound();
    }

    #[Test]
    public function the_finance_page_does_not_offer_a_top_up_card(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/finance')
            ->assertOk()
            ->assertDontSee(trans('app.topup.pay_card_button'))
            ->assertDontSee(trans('app.topup.pay_card_title'));
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

    #[Test]
    public function the_pay_card_offers_iwon_even_without_a_contract_number(): void
    {
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
            ->assertSee(trans('app.topup.pay_card_button'))
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
