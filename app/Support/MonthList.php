<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * The twelve months of the current year, as the month pickers expect them:
 * a translated name plus the "Y-m" value the SOLA API takes.
 */
final class MonthList
{
    /**
     * @return array<int, array{name: string, month: string}>
     */
    public static function forCurrentYear(): array
    {
        $year = CarbonImmutable::now()->year;

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'name' => (string) trans("app.months.{$month}"),
                'month' => CarbonImmutable::create($year, $month, 1)->format('Y-m'),
            ];
        }

        return $months;
    }
}
