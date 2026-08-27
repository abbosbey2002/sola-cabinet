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
 * Billing added range filtering (detail_begin/detail_end) to /traffic/detail,
 * so a period is now served in one call instead of one call per month it
 * covers — see BillingHistory::traffic() and SolaClient::trafficDetail().
 */
final class BillingHistoryTrafficTest extends TestCase
{
    #[Test]
    public function a_multi_month_period_makes_exactly_one_request(): void
    {
        Http::fake(['*' => Http::response(['detail' => []])]);

        $period = Period::between('2026-06-01', '2026-08-15');

        $this->history()->traffic('1001', $period);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->body() === json_encode([
            'acc_id' => '1001',
            'detail_begin' => '01.06.2026',
            'detail_end' => '15.08.2026',
            'lang' => 'ru',
        ]));
    }

    #[Test]
    public function rows_outside_the_requested_days_are_still_trimmed(): void
    {
        Http::fake(['*' => Http::response(['detail' => [
            ['event_time' => '2026-06-10 09:00:00', 'traffic_input' => 1_048_576, 'traffic_output' => 0],
            // Billing answers the whole range it was asked for; a boundary
            // row one second outside it must not slip into the total.
            ['event_time' => '2026-06-15 00:00:01', 'traffic_input' => 2_097_152, 'traffic_output' => 0],
        ]])]);

        $result = $this->history()->traffic('1001', Period::between('2026-06-01', '2026-06-14'));

        $this->assertSame(['2026-06-10 09:00:00'], array_column($result['rows'], 'event_time'));
        $this->assertSame(1.0, $result['input']);
        $this->assertFalse($result['incomplete']);
    }

    #[Test]
    public function a_failed_response_reports_incomplete_with_no_partial_rows(): void
    {
        Http::fake(['*' => Http::response(['code' => 500, 'errMsg' => 'billing down'], 500)]);

        $result = $this->history()->traffic('1001', Period::currentMonth());

        $this->assertSame([], $result['rows']);
        $this->assertSame(0.0, $result['input']);
        $this->assertSame(0.0, $result['output']);
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
