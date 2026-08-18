<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Which billing tariffs the admin has hidden from subscribers.
 *
 * The single source of truth for both what /tariffs shows and what
 * TariffController::connect() will accept — the two must never drift apart,
 * or a hidden tariff would still be connectable by posting its id directly.
 */
final class TariffVisibility
{
    /**
     * @return list<int>
     */
    public function disabledIds(): array
    {
        return DB::table('disabled_tariffs')->pluck('tariff_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function isEnabled(int $tariffId): bool
    {
        return ! DB::table('disabled_tariffs')->where('tariff_id', $tariffId)->exists();
    }

    public function disable(int $tariffId): void
    {
        DB::table('disabled_tariffs')->updateOrInsert(['tariff_id' => $tariffId], []);
    }

    public function enable(int $tariffId): void
    {
        DB::table('disabled_tariffs')->where('tariff_id', $tariffId)->delete();
    }

    /**
     * Strips the disabled tariffs out of a /tariff/available response.
     *
     * @param  array<int, array<string, mixed>>  $tariffs
     * @return list<array<string, mixed>>
     */
    public function filter(array $tariffs): array
    {
        $disabled = $this->disabledIds();

        return array_values(array_filter(
            $tariffs,
            fn (array $tariff): bool => ! in_array((int) $tariff['tariff_id'], $disabled, true),
        ));
    }
}
