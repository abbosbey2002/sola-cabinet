<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-day and per-month counts built nightly by `activity:rollup`, so the
 * statistics pages keep working for periods whose raw events have already
 * been pruned (config('activity.retention.events_months')).
 *
 * `uniques` is COUNT(DISTINCT account_id) over the whole bucket, computed from
 * the raw rows — never a sum of the smaller buckets, which would count one
 * account once per day it came back.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('activity.connection');
    }

    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        foreach (['activity_daily' => 'day', 'activity_monthly' => 'month'] as $tableName => $bucket) {
            $schema->create($tableName, function (Blueprint $table) use ($bucket): void {
                $table->id();
                // Monthly buckets store the first day of the month.
                $table->date($bucket);
                $table->string('event', 48);
                $table->unsignedInteger('hits')->default(0);
                $table->unsignedInteger('uniques')->default(0);
                $table->unsignedInteger('ok')->default(0);
                $table->unsignedInteger('fail')->default(0);
                $table->unsignedInteger('denied')->default(0);
                $table->unsignedInteger('invalid')->default(0);
                $table->timestamp('built_at');

                $table->unique([$bucket, 'event']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->dropIfExists('activity_monthly');
        $schema->dropIfExists('activity_daily');
    }
};
