<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Activity\ActivityRecorder;
use Illuminate\Console\Command;

/**
 * Run the activity-database migrations (database/migrations/activity) on
 * their own connection, with their own migrations table there.
 *
 * A no-op while ACTIVITY_ENABLED is off, so scripts/deploy.sh can call it
 * unconditionally on a server that has no PostgreSQL yet.
 */
final class ActivityMigrateCommand extends Command
{
    protected $signature = 'activity:migrate {--force : Run in production without asking}';

    protected $description = 'Migrate the activity journal database (PostgreSQL)';

    public function handle(): int
    {
        if (! ActivityRecorder::isEnabled()) {
            $this->info('Activity journal is disabled (ACTIVITY_ENABLED=false) — nothing to migrate.');

            return self::SUCCESS;
        }

        return $this->call('migrate', [
            '--database' => config('activity.connection'),
            '--path' => 'database/migrations/activity',
            '--force' => (bool) $this->option('force'),
        ]);
    }
}
