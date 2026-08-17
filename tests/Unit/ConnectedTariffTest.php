<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Sola\SolaResponse;
use App\Support\ConnectedTariff;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The charge date the dashboard's day meter is drawn from.
 *
 * Billing sends no charge date, so it is derived from the day the tariff
 * started: the period ends on that day of the month, every month. The
 * subscriber plans a payment around the result, so every boundary here is
 * pinned rather than assumed.
 */
final class ConnectedTariffTest extends TestCase
{
    #[Test]
    public function the_row_is_paired_on_the_id_and_never_on_the_name(): void
    {
        // Billing pads some tariff names with a trailing space, so the id is
        // the only safe key — and a non-matching row must not be borrowed.
        $tariff = $this->connected([
            ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun', 'date_begin' => '2024-01-02 09:00:00'],
            ['tariff_id' => '839', 'tariff_name' => 'Home 100 ', 'date_begin' => '2026-08-10 16:34:27'],
        ], '839');

        $this->assertNotNull($tariff);
        $this->assertSame('2026-08-10', $tariff->startedAt()?->format('Y-m-d'));
    }

    #[Test]
    public function nothing_to_pair_with_reports_no_tariff(): void
    {
        // An account whose current tariff is absent from the permits, an empty
        // list, a profile with no tariff at all, and a failed call.
        $this->assertNull($this->connected([['tariff_id' => '9', 'date_begin' => '2024-01-02 09:00:00']], '839'));
        $this->assertNull($this->connected([], '839'));
        $this->assertNull($this->connected([['tariff_id' => '839', 'date_begin' => '2026-08-10 16:34:27']], null));
        $this->assertNull(ConnectedTariff::current(new SolaResponse(400, ['code' => 121]), '839'));
    }

    /**
     * Billing sends a timestamp in date_begin, not a date. The subscriber is
     * told which day their plan started; the minute it was keyed in is not
     * theirs to care about.
     */
    #[Test]
    public function the_timestamp_billing_sends_is_reduced_to_a_day(): void
    {
        $tariff = $this->connected([['tariff_id' => '839', 'date_begin' => '2026-08-10 16:34:27']], '839');

        $this->assertSame('2026-08-10 00:00:00', $tariff?->startedAt()?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function the_charge_falls_on_the_anchor_day_of_the_month_ahead(): void
    {
        $tariff = $this->connected([['tariff_id' => '839', 'date_begin' => '2026-06-10 16:34:27']], '839');

        // Anchor still ahead this month.
        $this->assertSame('2026-08-10', $this->charge($tariff, '2026-08-03'));

        // Anchor already behind — the charge moves to next month, not to a date
        // in the past.
        $this->assertSame('2026-09-10', $this->charge($tariff, '2026-08-13'));
    }

    /**
     * On the morning the money comes off, the charge is today — ChargeCycle
     * draws that as the last tick and the view says "charge today". Pushing it
     * a month out would blank the meter on the one day it matters most.
     */
    #[Test]
    public function the_anchor_day_itself_is_the_charge_day(): void
    {
        $tariff = $this->connected([['tariff_id' => '839', 'date_begin' => '2026-08-10 16:34:27']], '839');

        $this->assertSame('2026-09-10', $this->charge($tariff, '2026-09-10'));
    }

    /**
     * A tariff connected today is not charged today: the first period runs a
     * month from the start, so the start itself is never a charge date.
     */
    #[Test]
    public function a_tariff_connected_today_is_charged_next_month(): void
    {
        $tariff = $this->connected([['tariff_id' => '839', 'date_begin' => '2026-08-13 11:02:00']], '839');

        $this->assertSame('2026-09-13', $this->charge($tariff, '2026-08-13'));
    }

    /**
     * The anchor is walked forward, not added once. An account that has held
     * the same tariff for years is charged on its anchor day this month — not
     * on a date back in 2019.
     */
    #[Test]
    public function a_long_held_tariff_is_charged_on_its_anchor_day_this_month(): void
    {
        $tariff = $this->connected([['tariff_id' => '839', 'date_begin' => '2019-07-17 10:00:00']], '839');

        $this->assertSame('2026-09-17', $this->charge($tariff, '2026-08-20'));
    }

    /**
     * An anchor of the 31st clamps into a shorter month rather than rolling
     * past it — the same reading ChargeCycle takes when deriving a cycle start,
     * so the meter's two ends stay consistent.
     */
    #[Test]
    public function a_month_end_anchor_clamps_instead_of_overflowing(): void
    {
        $tariff = $this->connected([['tariff_id' => '839', 'date_begin' => '2026-01-31 08:00:00']], '839');

        $this->assertSame('2026-02-28', $this->charge($tariff, '2026-02-05'));
    }

    #[Test]
    public function a_start_date_billing_cannot_express_reports_nothing(): void
    {
        // "0000-00-00" is billing's "not set", and the field has been seen
        // missing outright.
        $this->assertNull($this->connected([['tariff_id' => '839', 'date_begin' => '0000-00-00']], '839')?->startedAt());
        $this->assertNull($this->connected([['tariff_id' => '839']], '839')?->nextChargeDate());
    }

    /** @param list<array<string, mixed>> $rows */
    private function connected(array $rows, ?string $currentTariffId): ?ConnectedTariff
    {
        return ConnectedTariff::current(new SolaResponse(200, ['tariffs' => $rows]), $currentTariffId);
    }

    private function charge(?ConnectedTariff $tariff, string $today): ?string
    {
        return $tariff?->nextChargeDate(CarbonImmutable::parse($today))?->format('Y-m-d');
    }
}
