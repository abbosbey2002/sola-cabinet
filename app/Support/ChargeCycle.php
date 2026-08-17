<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * The billing cycle that ends at the subscriber's next charge, expressed as
 * countable days.
 *
 * This exists for the day meter on the dashboard — the one element the whole
 * redesign is built around. A subscriber has exactly one question, "will my
 * balance last?", and a percentage bar does not answer it: what answers it is
 * how many days are left and how much comes off on the last one.
 *
 * Billing reports only the NEXT charge date, never the cycle's start, so the
 * start is taken as one month back. That is right for the monthly tariffs the
 * cabinet actually sells and, for anything else, still produces an honest
 * "days remaining" — which is the number the meter is read for.
 *
 * The span is therefore always one calendar month, 28–31 days; the clamp on it
 * is a guard against a future change to how the start is derived, not against
 * anything billing can send today. What billing CAN make absurd is daysLeft,
 * which is why the view prints it through a plural string rather than doing
 * arithmetic of its own.
 */
final class ChargeCycle
{
    /** One tick per day, and no month is longer than this. */
    public const MAX_DAYS = 31;

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $today,
        /** Ticks to draw, 1..31. */
        public readonly int $totalDays,
        /** Which tick is today, 1-based. 0 when the cycle has not started. */
        public readonly int $currentDay,
        /** Whole days until the charge; 0 on the charge day and after it. */
        public readonly int $daysLeft,
    ) {}

    public static function endingAt(CarbonImmutable $nextCharge, ?CarbonImmutable $today = null): self
    {
        $today ??= CarbonImmutable::now();

        $end = $nextCharge->startOfDay();
        // NoOverflow, because plain subMonth() rolls forward when the target
        // month is shorter: 31 March minus a month is 3 March, not 28 February,
        // which would give every subscriber charged on the 29th–31st a 28-tick
        // meter and a visibly wrong cycle-start date beside it.
        $start = $end->subMonthNoOverflow();
        $today = $today->startOfDay();

        $total = max(1, min(self::MAX_DAYS, (int) $start->diffInDays($end)));

        // Before the cycle starts no tick is spent; after it ends every tick
        // is. In between, day one is the start date itself, hence the +1.
        $current = match (true) {
            $today->lessThan($start) => 0,
            $today->greaterThanOrEqualTo($end) => $total,
            default => min($total, (int) $start->diffInDays($today) + 1),
        };

        return new self(
            start: $start,
            end: $end,
            today: $today,
            totalDays: $total,
            currentDay: $current,
            daysLeft: max(0, (int) $today->diffInDays($end)),
        );
    }

    /** The charge lands on the last tick, which is what the meter marks amber. */
    public function chargeDay(): int
    {
        return $this->totalDays;
    }

    /**
     * The charge date is behind us — distinct from "today is the charge day",
     * which also has daysLeft === 0 but is not overdue: on the morning the
     * money comes off, "the charge date has passed" is simply wrong.
     */
    public function isOverdue(): bool
    {
        return $this->today->greaterThan($this->end);
    }

    public function isChargeDay(): bool
    {
        return $this->today->isSameDay($this->end);
    }
}
