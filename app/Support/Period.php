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
 * (pay_begin/pay_end); BillingHistory::payments() uses startInput()/
 * endInput() instead of months().
 *
 * The month count is capped because each traffic month is a separate HTTP
 * round trip to a billing server that answers in ~250 ms; an unbounded range
 * would let a subscriber hold a PHP worker open for a minute. The same cap
 * also bounds how wide a single payments range can be requested.
 */
final class Period
{
    public const MAX_MONTHS = 12;

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    /**
     * Build from two "Y-m-d" strings, normalising the order and clamping the
     * length. Both ends are inclusive: the end date covers its whole day.
     */
    public static function between(string $start, string $end): self
    {
        $from = CarbonImmutable::parse($start)->startOfDay();
        $to = CarbonImmutable::parse($end)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        // Clamp from the end backwards: the recent side is the one people mean.
        $earliest = $to->startOfMonth()->subMonths(self::MAX_MONTHS - 1);

        if ($from->lessThan($earliest)) {
            $from = $earliest;
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
     * True when the requested range was longer than MAX_MONTHS and got cut,
     * so the UI can say so rather than quietly showing partial totals.
     */
    public function wasClamped(string $requestedStart): bool
    {
        return CarbonImmutable::parse($requestedStart)->startOfDay()->lessThan($this->start);
    }

    public function startInput(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function endInput(): string
    {
        return $this->end->format('Y-m-d');
    }
}
