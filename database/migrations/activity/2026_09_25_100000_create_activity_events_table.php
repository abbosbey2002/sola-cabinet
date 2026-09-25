<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per thing a subscriber did in the cabinet: a menu page opened, an
 * action posted, a table printed or searched. See App\Support\Activity.
 *
 * Every column past `outcome` is nullable on purpose: a sign-in attempt has
 * no account yet, a server action has no screen size, and a failed geo lookup
 * is not a reason to lose the event.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('activity.connection');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('activity_events', function (Blueprint $table): void {
            $table->id();

            // occurred_on duplicates the date part of occurred_at so the
            // roll-ups and daily charts group on a plain column — the same SQL
            // on PostgreSQL and on the test suite's SQLite.
            $table->timestamp('occurred_at');
            $table->date('occurred_on');
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('event', 48);
            $table->string('outcome', 12);
            $table->string('error_code', 16)->nullable();
            $table->string('error_message', 255)->nullable();

            $table->string('account_id', 32)->nullable();
            $table->string('billing_login', 64)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('full_name', 255)->nullable();
            $table->smallInteger('abon_type')->nullable();
            $table->boolean('is_legal_entity')->nullable();

            $table->ipAddress('ip')->nullable();
            $table->string('ip_forwarded', 255)->nullable();
            $table->string('geo_country', 64)->nullable();
            $table->string('geo_city', 128)->nullable();

            $table->string('user_agent', 512)->nullable();
            $table->string('browser', 32)->nullable();
            $table->string('browser_version', 32)->nullable();
            $table->string('os', 32)->nullable();
            $table->string('os_version', 32)->nullable();
            $table->string('device_type', 16)->nullable();
            $table->string('device_model', 64)->nullable();

            $table->unsignedInteger('screen_w')->nullable();
            $table->unsignedInteger('screen_h')->nullable();
            $table->unsignedInteger('viewport_w')->nullable();
            $table->string('theme', 8)->nullable();
            $table->string('text_size', 8)->nullable();

            $table->string('route', 64)->nullable();
            $table->string('method', 8)->nullable();
            $table->string('path', 255)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->string('locale', 5)->nullable();

            $table->jsonb('meta')->nullable();

            $table->index('occurred_at');
            $table->index(['event', 'occurred_at']);
            $table->index(['account_id', 'occurred_at']);
            $table->index('occurred_on');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('activity_events');
    }
};
