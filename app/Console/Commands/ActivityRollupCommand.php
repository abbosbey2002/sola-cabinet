<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Activity\ActivityRollup;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Rebuild activity_daily for one day and activity_monthly for its month from
 * the raw journal. Idempotent: running it twice gives the same rows, so the
 * scheduler double-firing or a manual re-run after a fix is harmless.
 */
final class ActivityRollupCommand extends Command
{
    protected $signature = 'activity:rollup
        {--date= : Day to rebuild, Y-m-d (default: yesterday, UTC)}
        {--days=1 : How many days back from --date to rebuild}';

    protected $description = 'Build daily and monthly activity totals';

    public function handle(ActivityRollup $rollup): int
    {
        try {
            $last = $this->option('date')
                ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->option('date'), 'UTC')
                : CarbonImmutable::yesterday('UTC');
        } catch (InvalidArgumentException) {
            $last = false;
        }

        if ($last === false) {
            $this->error('--date must be Y-m-d.');

            return self::FAILURE;
        }

        $days = max(1, min(400, (int) $this->option('days')));
        $months = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $last->subDays($i);
            $rollup->day($day);
            $months[$day->format('Y-m')] = $day;
        }

        foreach ($months as $day) {
            $rollup->month($day);
        }

        $this->info(sprintf('Rolled up %d day(s) and %d month(s) ending %s.', $days, count($months), $last->format('Y-m-d')));

        return self::SUCCESS;
    }
}
