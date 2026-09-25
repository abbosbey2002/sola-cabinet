<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every "change tariff" attempt, whatever came of it — the money-flow audit
 * behind /admin/tariff-changes. See App\Support\Activity\TariffChangeRecorder.
 *
 * Money columns are soʻm as decimal(14,2), never float: /abonent/info.saldo
 * carries kopecks, and /tariff/available.cost (tiyin) is divided by 100 before
 * it is stored so every price in this table is in the same unit.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('activity.connection');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('tariff_changes', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('billing_duration_ms')->nullable();

            // The account as it was at the moment of the attempt.
            $table->string('account_id', 32);
            $table->string('billing_login', 64)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('full_name', 255)->nullable();
            $table->smallInteger('abon_type')->nullable();
            $table->boolean('is_legal_entity')->nullable();
            $table->string('account_status', 64)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->date('contract_date')->nullable();

            $table->decimal('balance_before', 14, 2)->nullable();
            $table->date('next_charge_date')->nullable();

            $table->string('old_tariff_id', 16)->nullable();
            $table->string('old_tariff_name', 255)->nullable();
            $table->decimal('old_tariff_price', 14, 2)->nullable();
            $table->date('old_tariff_connected_at')->nullable();

            $table->string('new_tariff_id', 16);
            $table->string('new_tariff_name', 255)->nullable();
            $table->decimal('new_tariff_price', 14, 2)->nullable();
            $table->string('new_tariff_speed', 32)->nullable();
            $table->string('new_tariff_period', 32)->nullable();

            $table->string('timing', 8)->nullable();
            $table->date('effective_date')->nullable();

            $table->string('result', 24);
            $table->string('error_code', 16)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->string('denied_reason', 32)->nullable();

            $table->decimal('balance_after', 14, 2)->nullable();
            $table->string('pending_tariff_after', 255)->nullable();

            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_type', 16)->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('request_id', 64)->nullable();

            $table->jsonb('profile_snapshot')->nullable();

            $table->index('created_at');
            $table->index(['account_id', 'created_at']);
            $table->index(['result', 'created_at']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('tariff_changes');
    }
};
