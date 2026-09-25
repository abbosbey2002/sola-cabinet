<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Carbon\CarbonImmutable;

/**
 * The inclusive day range an admin report covers, in UTC like everything the
 * journal stores. Defaults to the last 30 days including today.
 */
final readonly class ReportPeriod
{
    public const DEFAULT_DAYS = 30;

    /** The longest range a report accepts — a year of raw events is enough to scan. */
    public const MAX_DAYS = 366;

    private function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    public static function between(?string $from, ?string $to): self
    {
        $today = CarbonImmutable::today('UTC');

        $end = self::parse($to) ?? $today;
        $start = self::parse($from) ?? $end->subDays(self::DEFAULT_DAYS - 1);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) >= self::MAX_DAYS) {
            $start = $end->subDays(self::MAX_DAYS - 1);
        }

        return new self($start, $end);
    }

    public function fromDate(): string
    {
        return $this->from->format('Y-m-d');
    }

    public function toDate(): string
    {
        return $this->to->format('Y-m-d');
    }

    /** For timestamp columns: the first second of the first day. */
    public function startsAt(): string
    {
        return $this->from->startOfDay()->format('Y-m-d H:i:s');
    }

    /** For timestamp columns: the last second of the last day. */
    public function endsAt(): string
    {
        return $this->to->endOfDay()->format('Y-m-d H:i:s');
    }

    /** @return list<string> every day in the range, Y-m-d */
    public function days(): array
    {
        $days = [];

        for ($day = $this->from; $day->lessThanOrEqualTo($this->to); $day = $day->addDay()) {
            $days[] = $day->format('Y-m-d');
        }

        return $days;
    }

    private static function parse(?string $value): ?CarbonImmutable
    {
        if ($value === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');

        return $date === false || $date->format('Y-m-d') !== $value ? null : $date;
    }
}
