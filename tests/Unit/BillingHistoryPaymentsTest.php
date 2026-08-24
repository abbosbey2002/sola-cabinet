<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Sola\SolaClient;
use App\Support\BillingHistory;
use App\Support\Period;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Billing added range filtering (pay_begin/pay_end) to /acct/payments, so a
 * period is now served in one call instead of one call per month it covers —
 * see BillingHistory::payments() and SolaClient::payments().
 */
final class BillingHistoryPaymentsTest extends TestCase
{
    #[Test]
    public function a_multi_month_period_makes_exactly_one_request(): void
    {
        Http::fake(['*' => Http::response(['payments' => []])]);

        $period = Period::between('2026-06-01', '2026-08-15');
        $this->assertSame(['2026-06', '2026-07', '2026-08'], $period->months(), 'the fixture should span 3 months');

        $this->history()->payments('1001', $period);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->body() === json_encode([
            'acc_id' => '1001',
            'pay_begin' => '2026-06-01',
            'pay_end' => '2026-08-15',
            'lang' => 'ru',
        ]));
    }

    #[Test]
    public function rows_outside_the_requested_days_are_still_trimmed(): void
    {
        Http::fake(['*' => Http::response(['payments' => [
            ['payment_id' => '1', 'payment_date' => '2026-06-10 09:00:00', 'amount' => 100_000],
            // Billing answers the whole range it was asked for; a boundary
            // row one second outside it must not slip into the total.
            ['payment_id' => '2', 'payment_date' => '2026-06-15 00:00:01', 'amount' => 200_000],
        ]])]);

        $result = $this->history()->payments('1001', Period::between('2026-06-01', '2026-06-14'));

        $this->assertSame(['1'], array_column($result['rows'], 'payment_id'));
        $this->assertSame(1000.0, $result['total']);
        $this->assertFalse($result['incomplete']);
    }

    #[Test]
    public function a_failed_response_reports_incomplete_with_no_partial_rows(): void
    {
        Http::fake(['*' => Http::response(['code' => 500, 'errMsg' => 'billing down'], 500)]);

        $result = $this->history()->payments('1001', Period::currentMonth());

        $this->assertSame([], $result['rows']);
        $this->assertSame(0.0, $result['total']);
        $this->assertTrue($result['incomplete']);
    }

    private function history(): BillingHistory
    {
        return new BillingHistory(new SolaClient(app(Factory::class), [
            'base_url' => 'http://sola.test/',
            'username' => 'test',
            'password' => 'secret',
            'secret_key' => 'secret-key',
        ]));
    }
}
