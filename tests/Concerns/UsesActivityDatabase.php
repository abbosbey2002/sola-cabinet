<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Switches the activity journal on and gives it a fresh in-memory SQLite
 * database for each test (phpunit.xml points the "activity" connection at
 * :memory:), migrated from database/migrations/activity like production.
 */
trait UsesActivityDatabase
{
    protected function setUpActivityDatabase(): void
    {
        config(['activity.enabled' => true]);

        DB::purge('activity');

        $this->artisan('migrate', [
            '--database' => 'activity',
            '--path' => 'database/migrations/activity',
        ])->assertSuccessful();
    }

    protected function activity(): ConnectionInterface
    {
        return DB::connection('activity');
    }
}
