<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Builds activity_daily / activity_monthly from activity_events.
 *
 * Each bucket is recomputed from the raw rows and replaced in one
 * transaction — never incremented — so re-running is always safe and
 * `uniques` is a true COUNT(DISTINCT account_id) for the whole bucket.
 */
final class ActivityRollup
{
    public function day(CarbonImmutable $day): void
    {
        $date = $day->format('Y-m-d');

        $this->rebuild('activity_daily', 'day', $date, $date, $date);
    }

    public function month(CarbonImmutable $anyDayInMonth): void
    {
        $first = $anyDayInMonth->startOfMonth();

        $this->rebuild(
            'activity_monthly',
            'month',
            $first->format('Y-m-d'),
            $first->format('Y-m-d'),
            $first->endOfMonth()->format('Y-m-d'),
        );
    }

    private function rebuild(string $table, string $bucketColumn, string $bucket, string $from, string $to): void
    {
        $db = $this->db();

        $totals = $db->table('activity_events')
            ->whereBetween('occurred_on', [$from, $to])
            ->groupBy('event')
            ->selectRaw('event')
            ->selectRaw('COUNT(*) AS hits')
            ->selectRaw('COUNT(DISTINCT account_id) AS uniques')
            ->selectRaw("SUM(CASE WHEN outcome = 'ok' THEN 1 ELSE 0 END) AS ok")
            ->selectRaw("SUM(CASE WHEN outcome = 'fail' THEN 1 ELSE 0 END) AS fail")
            ->selectRaw("SUM(CASE WHEN outcome = 'denied' THEN 1 ELSE 0 END) AS denied")
            ->selectRaw("SUM(CASE WHEN outcome = 'invalid' THEN 1 ELSE 0 END) AS invalid")
            ->get();

        $builtAt = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s');

        $rows = $totals->map(fn (object $total): array => [
            $bucketColumn => $bucket,
            'event' => (string) $total->event,
            'hits' => (int) $total->hits,
            'uniques' => (int) $total->uniques,
            'ok' => (int) $total->ok,
            'fail' => (int) $total->fail,
            'denied' => (int) $total->denied,
            'invalid' => (int) $total->invalid,
            'built_at' => $builtAt,
        ])->all();

        $db->transaction(function () use ($db, $table, $bucketColumn, $bucket, $rows): void {
            $db->table($table)->where($bucketColumn, $bucket)->delete();

            if ($rows !== []) {
                $db->table($table)->insert($rows);
            }
        });
    }

    private function db(): ConnectionInterface
    {
        return DB::connection(config('activity.connection'));
    }
}
