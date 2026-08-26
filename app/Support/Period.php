<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * A start/end date range, as the spec's "Период: с … по …" controls produce.
 *
 * /traffic/detail only accepts a single "Y-m" per call — there is no range
 * endpoint — so BillingHistory::traffic() asks for each month the range
 * touches and trims the rows back to the exact days requested. months() and
 * contains() exist for that. /acct/payments takes a range directly
 * (pay_begin/pay_end); BillingHistory::payments() uses paymentsStart()/
 * paymentsEnd() instead of months().
 *
 * There used to be a 12-month cap here: each traffic month is a separate
 * HTTP round trip to a billing server that answers in ~250 ms, so a very
 * long range means a slow page rather than a wrong one. Removed on the
 * client's explicit request (2026-08-25), after that trade-off was raised —
 * a subscriber can now request any range, at the cost of a slower traffic
 * page the wider it gets.
 */
final class Period
{
    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    /**
     * Build from two "Y-m-d" strings, normalising the order. Both ends are
     * inclusive: the end date covers its whole day.
     */
    public static function between(string $start, string $end): self
    {
        $from = CarbonImmutable::parse($start)->startOfDay();
        $to = CarbonImmutable::parse($end)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return new self($from, $to);
    }

    /**
     * The current calendar month, which is what the dashboard opens on.
     */
    public static function currentMonth(): self
    {
        $now = CarbonImmutable::now();

        return new self($now->startOfMonth(), $now->endOfDay());
    }

    /**
     * Every month the range touches, as the "Y-m" strings the API takes.
     *
     * @return list<string>
     */
    public function months(): array
    {
        $months = [];
        $cursor = $this->start->startOfMonth();
        $last = $this->end->startOfMonth();

        while ($cursor->lessThanOrEqualTo($last)) {
            $months[] = $cursor->format('Y-m');
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * Whether a date string from the API falls inside the range. Rows outside
     * it come back because the API answers per whole month, not per range.
     */
    public function contains(?string $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }

        $moment = CarbonImmutable::parse($date);

        return $moment->betweenIncluded($this->start, $this->end);
    }

    /**
     * "Y-m-d" — what an HTML `<input type="date">` requires in its `value`
     * attribute (period-form.blade.php). Distinct from paymentsStart()/
     * paymentsEnd() below, which speak the shape /acct/payments wants
     * instead — the two are not interchangeable even though both describe
     * the same two dates.
     */
    public function startInput(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function endInput(): string
    {
        return $this->end->format('Y-m-d');
    }

    /**
     * As /acct/payments actually wants pay_begin/pay_end: "d.m.Y" (4-digit
     * year), not the "Y-m-d" startInput()/endInput() above give the date
     * picker. The 2-digit "d.m.y" this shipped with on 2026-08-25 was wrong —
     * corrected 2026-08-27, see docs/api/SOLA_API_REFERENCE.md §7.
     */
    public function paymentsStart(): string
    {
        return $this->start->format('d.m.Y');
    }

    public function paymentsEnd(): string
    {
        return $this->end->format('d.m.Y');
    }
}
