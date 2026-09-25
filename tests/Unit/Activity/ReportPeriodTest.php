<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use App\Support\Activity\ReportPeriod;
use App\Support\Activity\TariffChangeResult;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReportPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-25 15:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function the_default_is_the_last_thirty_days_including_today(): void
    {
        $period = ReportPeriod::between(null, null);

        $this->assertSame('2026-08-27', $period->fromDate());
        $this->assertSame('2026-09-25', $period->toDate());
        $this->assertCount(30, $period->days());
        $this->assertSame('2026-09-25 23:59:59', $period->endsAt());
    }

    #[Test]
    public function a_reversed_range_is_turned_around(): void
    {
        $period = ReportPeriod::between('2026-09-10', '2026-09-01');

        $this->assertSame(['2026-09-01', '2026-09-10'], [$period->fromDate(), $period->toDate()]);
    }

    #[Test]
    public function a_range_longer_than_a_year_is_cut_to_one(): void
    {
        $period = ReportPeriod::between('2020-01-01', '2026-09-25');

        $this->assertCount(ReportPeriod::MAX_DAYS, $period->days());
        $this->assertSame('2026-09-25', $period->toDate());
    }

    #[Test]
    public function an_impossible_date_falls_back_to_the_default(): void
    {
        $this->assertSame('2026-08-27', ReportPeriod::between('2026-02-31', null)->fromDate());
    }

    #[Test]
    public function only_code_129_means_not_enough_money(): void
    {
        $this->assertSame(TariffChangeResult::InsufficientFunds, TariffChangeResult::ofBillingCode('129'));
        $this->assertSame(TariffChangeResult::BillingError, TariffChangeResult::ofBillingCode('132'));
        $this->assertSame(TariffChangeResult::BillingError, TariffChangeResult::ofBillingCode(null));
    }
}
