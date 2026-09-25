<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * A signed-in subscriber against a faked billing API — the same shape as
 * CabinetTest::fakeSola() / verifiedSubscriber(), shared by the activity
 * journal tests.
 */
trait FakesSubscriberBilling
{
    protected const ACCOUNT_ID = '1001';

    /**
     * @param  array<string, mixed>  $overrides  URL pattern => response, checked first
     */
    protected function fakeBilling(array $overrides = []): void
    {
        Http::fake($overrides + [
            '*/identify' => Http::response([
                'accs' => [
                    ['accId' => 1001, 'abonType' => 2, 'abonName' => 'Tester Testov', 'login' => '998901234567'],
                    ['accId' => 1002, 'abonType' => 2, 'abonName' => 'Second Account', 'login' => 'D-1002'],
                ],
            ]),
            '*/verify' => Http::response(['code' => 0]),
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'email' => 'tester@sola.uz',
                'phone' => '998901234567',
                'address' => 'Tashkent',
                'contract_date' => '2019-09-05',
                'status' => 'Активен',
                'saldo' => '125000.50',
                'curr_tariff_id' => '839',
                'curr_tariff_name' => 'Home 100',
                'tariff_price' => '100000',
                'legal' => 'Физическое лицо',
            ]),
            '*/device/list' => Http::response(['devices' => []]),
            '*/acct/payments' => Http::response(['payments' => []]),
            '*/traffic/detail' => Http::response(['detail' => []]),
            '*/tariff/available' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun ', 'cost' => 2500000, 'tspd' => '500', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
                    ['tariff_id' => '412', 'tariff_name' => 'Turbo 200', 'cost' => 4500000, 'tspd' => '200', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
                ],
            ]),
            '*/tariff/connected' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '839', 'tariff_name' => 'Home 100', 'date_begin' => '2026-08-10 16:34:27', 'date_end' => null, 'tariff_isoff' => '0'],
                ],
            ]),
            '*/tariff/connect' => Http::response(['code' => 0]),
            '*/device/new' => Http::response(['code' => 0]),
            '*' => Http::response([], 200),
        ]);
    }

    protected function verifiedSubscriber(int $type = 2, string $accountId = self::ACCOUNT_ID): self
    {
        return $this->withCookies([
            'verify' => '1',
            'account' => $accountId,
            'login' => '998901234567',
            'phone' => '998901234567',
            'billing_login' => '998901234567',
            'full_name' => 'Tester Testov',
            'data' => json_encode(['type' => $type]),
        ]);
    }
}
