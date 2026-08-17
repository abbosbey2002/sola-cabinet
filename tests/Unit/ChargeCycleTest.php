<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ChargeCycle;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The arithmetic behind the day meter — the one element the dashboard is built
 * around. Every value here is drawn on screen as a tick or printed beside it,
 * so an off-by-one is visible to the subscriber.
 */
final class ChargeCycleTest extends TestCase
{
    /**
     * The bug this pins: plain subMonth() rolls FORWARD out of a short month,
     * so 31 March minus one month is 3 March, not 28 February. That gave every
     * subscriber charged on the 29th–31st a 28-tick meter and a cycle-start
     * date three days before the charge.
     */
    #[Test]
    public function a_month_end_charge_date_does_not_overflow_into_the_short_month(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2026-03-31'),
            CarbonImmutable::parse('2026-03-10'),
        );

        $this->assertSame('2026-02-28', $cycle->start->format('Y-m-d'));
        $this->assertSame(31, $cycle->totalDays);
        $this->assertSame(21, $cycle->daysLeft);
    }

    #[Test]
    public function a_whole_month_is_one_tick_per_day_with_today_and_the_charge_marked(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-08-08'),
        );

        $this->assertSame('2026-08-01', $cycle->start->format('Y-m-d'));
        $this->assertSame(31, $cycle->totalDays);
        // The 1st is tick 1, so the 8th is tick 8.
        $this->assertSame(8, $cycle->currentDay);
        $this->assertSame(31, $cycle->chargeDay());
        $this->assertSame(24, $cycle->daysLeft);
        $this->assertFalse($cycle->isChargeDay());
        $this->assertFalse($cycle->isOverdue());
    }

    /**
     * daysLeft is 0 on the charge day AND after it, so the two states have to
     * be told apart some other way: "the charge date has passed" is wrong on
     * the morning the money actually comes off.
     */
    #[Test]
    public function the_charge_day_itself_is_not_overdue(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-09-01 09:00:00'),
        );

        $this->assertSame(0, $cycle->daysLeft);
        $this->assertTrue($cycle->isChargeDay());
        $this->assertFalse($cycle->isOverdue());
        $this->assertSame($cycle->totalDays, $cycle->currentDay);
    }

    #[Test]
    public function a_charge_date_already_behind_us_reports_overdue(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-09-05'),
        );

        $this->assertSame(0, $cycle->daysLeft);
        $this->assertFalse($cycle->isChargeDay());
        $this->assertTrue($cycle->isOverdue());
        // Every tick is spent — the meter is full, not empty.
        $this->assertSame($cycle->totalDays, $cycle->currentDay);
    }

    /**
     * Billing has sent charge dates far in the future. Before the cycle opens
     * no day has been spent, and the meter must not mark a "today" tick that
     * has not arrived.
     */
    #[Test]
    public function a_cycle_that_has_not_started_has_no_spent_days(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2027-01-15'),
            CarbonImmutable::parse('2026-08-08'),
        );

        $this->assertSame(0, $cycle->currentDay);
        $this->assertGreaterThan(0, $cycle->daysLeft);
    }

    #[Test]
    public function a_february_cycle_draws_only_its_own_days(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2026-03-01'),
            CarbonImmutable::parse('2026-02-10'),
        );

        $this->assertSame('2026-02-01', $cycle->start->format('Y-m-d'));
        $this->assertSame(28, $cycle->totalDays);
        $this->assertSame(10, $cycle->currentDay);
    }

    /** A leap February is 29 ticks, not 28. */
    #[Test]
    public function a_leap_february_draws_twenty_nine_days(): void
    {
        $cycle = ChargeCycle::endingAt(
            CarbonImmutable::parse('2028-03-01'),
            CarbonImmutable::parse('2028-02-10'),
        );

        $this->assertSame(29, $cycle->totalDays);
    }
}
