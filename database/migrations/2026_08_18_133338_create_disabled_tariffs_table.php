<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Presence of a row hides that tariff_id from /tariffs and from
     * TariffController::connect(). Absence is "enabled" — a tariff billing
     * introduces tomorrow is visible until an admin explicitly hides it.
     */
    public function up(): void
    {
        Schema::create('disabled_tariffs', function (Blueprint $table): void {
            // Billing's own SRV_ID (docs/api/SOLA_API_REFERENCE.md §13) — a
            // catalog id, stable across every account, not a local key.
            $table->unsignedInteger('tariff_id')->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disabled_tariffs');
    }
};
