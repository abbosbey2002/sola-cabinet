<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Sola\SolaResponse;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * The tariff in force, as /tariff/connected reports it.
 *
 * AbonentProfile is built from one /abonent/info response and stops where that
 * response stops: it knows the current tariff's name and id but not the day it
 * started. That day is what the charge date follows, and until 2026-08-13 no
 * endpoint was known to carry it — see the note in AbonentProfile about the
 * charge date deliberately having no fallback.
 *
 * /tariff/connected does carry it, in date_begin. Probing the live API found it
 * returns the active permits only, one row per tariff, keyed by the same
 * tariff_id /abonent/info reports as curr_tariff_id — so the pairing is made on
 * the id and never on the name, which billing pads with trailing spaces.
 *
 * This is not a history: a tariff that has been replaced does not appear here.
 */
final class ConnectedTariff
{
    /** @param array<string, mixed> $row */
    private function __construct(private readonly array $row) {}

    /**
     * The row for the tariff /abonent/info calls current.
     *
     * Null when there is nothing to pair: a failed call, an account with no
     * tariff, or a current tariff that is not among the connected permits.
     * Every caller renders an absence rather than a placeholder date.
     */
    public static function current(SolaResponse $connected, ?string $currentTariffId): ?self
    {
        if ($currentTariffId === null || $connected->failed()) {
            return null;
        }

        foreach ((array) $connected->get('tariffs', []) as $row) {
            if (is_array($row) && (string) ($row['tariff_id'] ?? '') === $currentTariffId) {
                return new self($row);
            }
        }

        return null;
    }

    /**
     * The day the tariff took effect.
     *
     * Billing sends a timestamp here ("2026-08-10 16:34:27"), not a date. The
     * time is dropped: the subscriber is being told which day their plan
     * started, and the minute it was keyed in is billing's business.
     */
    public function startedAt(): ?CarbonImmutable
    {
        $value = $this->row['date_begin'] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }

        // Billing sends "0000-00-00" for "not set", and Carbon does not throw on
        // it — it parses to year -1. Left alone that reaches nextChargeDate(),
        // which would walk two thousand years forward a month at a time to reach
        // today. Nothing billed here started before the network did.
        return $date->year < 2000 ? null : $date->startOfDay();
    }

    /**
     * The next date the subscriber is charged.
     *
     * The rule, from the client on 2026-08-13: a tariff's period ends on the
     * day of the month it started, and the next one begins the same day — so
     * the charge falls on that anchor day, every month.
     *
     * Walked forward from the start rather than added once: a tariff connected
     * two years ago is still charged on its anchor day, not on a date in 2024.
     * The first charge is a month after the start, never the start itself, so a
     * tariff connected today is not reported as charging today.
     *
     * NoOverflow, so an anchor of the 31st lands on the 28th–30th in a shorter
     * month instead of rolling into the next one. Billing has not stated what
     * it does with the 31st; this is the reading ChargeCycle already takes when
     * it derives a cycle start.
     */
    public function nextChargeDate(?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $start = $this->startedAt();

        if ($start === null) {
            return null;
        }

        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        $charge = $start->addMonthNoOverflow();

        while ($charge->lessThan($today)) {
            $charge = $charge->addMonthNoOverflow();
        }

        return $charge;
    }

    /**
     * Whether billing has marked the permit switched off. Live data returns
     * "0" for a tariff in normal service.
     */
    public function isOff(): bool
    {
        return (string) ($this->row['tariff_isoff'] ?? '0') === '1';
    }
}
