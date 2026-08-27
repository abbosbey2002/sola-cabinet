<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Period;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PeriodTest extends TestCase
{
    /**
     * lastMonth() must floor its start to the beginning of the day. Without
     * startOfDay(), the start carries the current wall-clock time, so a
     * boundary-day row earlier in the day than "now" would fall outside
     * contains() and silently drop from the traffic totals — a loss that
     * changes with the time of day the subscriber happens to load the page.
     */
    #[Test]
    public function last_month_contains_every_hour_of_its_first_day(): void
    {
        $period = Period::lastMonth();
        $firstDay = CarbonImmutable::now()->subMonth()->format('Y-m-d');

        $this->assertTrue($period->contains($firstDay.' 00:00:01'));
        $this->assertTrue($period->contains($firstDay.' 23:59:59'));
    }

    #[Test]
    public function last_month_ends_at_the_end_of_today(): void
    {
        $period = Period::lastMonth();

        $this->assertTrue($period->contains(CarbonImmutable::now()->format('Y-m-d').' 23:59:59'));
        $this->assertFalse($period->contains(CarbonImmutable::now()->addDay()->format('Y-m-d').' 00:00:00'));
    }
}
