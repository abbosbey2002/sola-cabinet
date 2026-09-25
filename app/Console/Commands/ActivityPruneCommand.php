<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Activity\TariffChangeResult;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Retention (config('activity.retention')): drop raw events, tariff changes
 * and admin audit rows past their keep-by date, and mark tariff changes left
 * `pending` by a dead request as `unknown`.
 *
 * The daily/monthly roll-ups are never pruned — they are what the long-range
 * statistics are built from once the raw rows are gone. Deletes go in batches
 * so a first run over a year of data does not hold one giant lock.
 */
final class ActivityPruneCommand extends Command
{
    private const BATCH = 5000;

    protected $signature = 'activity:prune';

    protected $description = 'Apply the activity journal retention policy';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        $retention = (array) config('activity.retention');

        $stale = $this->db()->table('tariff_changes')
            ->where('result', TariffChangeResult::Pending->value)
            ->where('created_at', '<', $now->subMinutes((int) config('activity.pending_timeout_minutes'))->format('Y-m-d H:i:s'))
            ->update(['result' => TariffChangeResult::Unknown->value, 'updated_at' => $now->format('Y-m-d H:i:s')]);

        $events = $this->prune('activity_events', 'occurred_at', $now->subMonths((int) $retention['events_months']));
        $changes = $this->prune('tariff_changes', 'created_at', $now->subYears((int) $retention['tariff_changes_years']));
        $audit = $this->prune('admin_audit', 'created_at', $now->subYears((int) $retention['admin_audit_years']));

        $this->info("Marked {$stale} stale tariff change(s) unknown; pruned {$events} event(s), {$changes} tariff change(s), {$audit} audit row(s).");

        return self::SUCCESS;
    }

    private function prune(string $table, string $column, CarbonImmutable $before): int
    {
        $deleted = 0;

        do {
            $ids = $this->db()->table($table)
                ->where($column, '<', $before->format('Y-m-d H:i:s'))
                ->orderBy('id')
                ->limit(self::BATCH)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += $this->db()->table($table)->whereIn('id', $ids->all())->delete();
        } while ($ids->count() === self::BATCH);

        return $deleted;
    }

    private function db(): ConnectionInterface
    {
        return DB::connection(config('activity.connection'));
    }
}
