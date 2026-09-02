<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * A start/end date range, as the spec's "Период: с … по …" controls produce.
 *
 * Both /acct/payments and /traffic/detail take a range directly now —
 * BillingHistory::payments() uses paymentsStart()/paymentsEnd() (pay_begin/
 * pay_end), BillingHistory::traffic() uses detailBegin()/detailEnd()
 * (detail_begin/detail_end). contains() still trims the rows defensively:
 * billing answers the range it was asked for, but boundary rows are checked
 * rather than trusted. months() is kept as a general "Y-m" breakdown even
 * though neither history call walks it anymore.
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
     * The trailing 30-ish days, which is what the traffic page opens on:
     * today minus one calendar month, through today.
     */
    public static function lastMonth(): self
    {
        $now = CarbonImmutable::now();

        return new self($now->subMonth()->startOfDay(), $now->endOfDay());
    }

    /**
     * The trailing calendar year through today — what the home "last payment"
     * card searches before it admits there were none.
     */
    public static function lastYear(): self
    {
        $now = CarbonImmutable::now();

        return new self($now->subYear()->startOfDay(), $now->endOfDay());
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

    /**
     * As /traffic/detail wants detail_begin/detail_end: "d.m.Y" (4-digit
     * year), the same shape /acct/payments takes for pay_begin/pay_end —
     * distinct from startInput()/endInput() above, which speak the date
     * picker's "Y-m-d" instead.
     */
    public function detailBegin(): string
    {
        return $this->start->format('d.m.Y');
    }

    public function detailEnd(): string
    {
        return $this->end->format('d.m.Y');
    }
}
