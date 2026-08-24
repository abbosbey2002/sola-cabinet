<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flips tariff visibility from a blacklist to a whitelist: presence of a
     * row is now what makes a tariff_id show on /tariffs, not its absence.
     * A tariff billing introduces tomorrow is hidden until an admin
     * explicitly opts it in — see app/Support/TariffVisibility.php.
     *
     * disabled_tariffs is dropped rather than migrated: under the old rule
     * its rows named the hidden tariffs, which is the opposite set from what
     * enabled_tariffs needs, so carrying them over would enable exactly the
     * tariffs that were supposed to stay hidden. enabled_tariffs starts
     * empty by design — nothing shows on /tariffs until an admin picks it.
     */
    public function up(): void
    {
        Schema::dropIfExists('disabled_tariffs');

        Schema::create('enabled_tariffs', function (Blueprint $table): void {
            // Billing's own SRV_ID (docs/api/SOLA_API_REFERENCE.md §13) — a
            // catalog id, stable across every account, not a local key.
            $table->unsignedInteger('tariff_id')->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enabled_tariffs');

        Schema::create('disabled_tariffs', function (Blueprint $table): void {
            $table->unsignedInteger('tariff_id')->primary();
            $table->timestamps();
        });
    }
};
