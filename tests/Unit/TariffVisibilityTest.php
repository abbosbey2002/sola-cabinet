<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TariffVisibility;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * enabled_tariffs lives in the same sqlite file as local dev — every test
 * here runs inside a transaction so nothing it writes survives the test, but
 * the table can still hold REAL rows an admin enabled outside this test run
 * (this machine's enabled_tariffs is not a fixture). So every id used here
 * is a reserved test-only value (900xxx) well outside SOLA's real SRV_ID
 * range (observed real ids: single/triple digits), never a real tariff_id —
 * anything asserting an exact set instead would be one real admin click away
 * from failing for a reason that has nothing to do with the code.
 */
final class TariffVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    private const TARIFF_A = 900001;

    private const TARIFF_B = 900002;

    #[Test]
    public function a_tariff_with_no_row_is_disabled(): void
    {
        $this->assertFalse((new TariffVisibility)->isEnabled(self::TARIFF_A));
    }

    #[Test]
    public function enable_then_disable_round_trips(): void
    {
        $visibility = new TariffVisibility;

        $visibility->enable(self::TARIFF_A);
        $this->assertTrue($visibility->isEnabled(self::TARIFF_A));

        $visibility->disable(self::TARIFF_A);
        $this->assertFalse($visibility->isEnabled(self::TARIFF_A));
    }

    #[Test]
    public function enabling_the_same_tariff_twice_does_not_error(): void
    {
        $visibility = new TariffVisibility;

        $visibility->enable(self::TARIFF_A);
        $visibility->enable(self::TARIFF_A);

        $this->assertTrue($visibility->isEnabled(self::TARIFF_A));
    }

    #[Test]
    public function enabled_ids_lists_every_visible_tariff(): void
    {
        $visibility = new TariffVisibility;

        $visibility->enable(self::TARIFF_A);
        $visibility->enable(self::TARIFF_B);

        $this->assertContains(self::TARIFF_A, $visibility->enabledIds());
        $this->assertContains(self::TARIFF_B, $visibility->enabledIds());
    }

    /**
     * The real API sends tariff_id as a numeric STRING
     * (docs/api/SOLA_API_REFERENCE.md §13: "'tariff_id' => 'SRV_ID'", type
     * string) while disable()/enable() take an int — filter() is the seam
     * between the two, so a string tariff_id has to match an enabled int id.
     */
    #[Test]
    public function filter_matches_a_string_tariff_id_against_an_enabled_int_id(): void
    {
        $visibility = new TariffVisibility;
        $visibility->enable(self::TARIFF_A);

        $tariffs = [
            ['tariff_id' => (string) self::TARIFF_A, 'tariff_name' => 'Home 100'],
            ['tariff_id' => (string) self::TARIFF_B, 'tariff_name' => 'Paket 30 kun'],
        ];

        $filtered = $visibility->filter($tariffs);

        $this->assertCount(1, $filtered);
        $this->assertSame((string) self::TARIFF_A, $filtered[0]['tariff_id']);
    }

    #[Test]
    public function filter_drops_a_tariff_that_was_never_enabled(): void
    {
        $tariffs = [
            ['tariff_id' => (string) self::TARIFF_A, 'tariff_name' => 'Home 100'],
        ];

        $this->assertSame([], (new TariffVisibility)->filter($tariffs));
    }
}
